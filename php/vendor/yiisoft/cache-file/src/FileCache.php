<?php

declare(strict_types=1);

namespace Yiisoft\Cache\File;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Traversable;

use function array_keys;
use function array_map;
use function closedir;
use function error_get_last;
use function filemtime;
use function fileowner;
use function fopen;
use function function_exists;
use function is_dir;
use function is_file;
use function iterator_to_array;
use function mkdir;
use function opendir;
use function posix_geteuid;
use function random_int;
use function readdir;
use function restore_error_handler;
use function rmdir;
use function serialize;
use function set_error_handler;
use function sprintf;
use function strpbrk;
use function substr;
use function unlink;
use function unserialize;

use const LOCK_EX;
use const LOCK_SH;
use const LOCK_UN;

/**
 * `FileCache` implements a cache handler using files.
 *
 * For each data value being cached, `FileCache` will store it in a separate file. The cache files are placed
 * under {@see FileCache::$cachePath}. `FileCache` will perform garbage collection automatically to remove expired
 * cache files.
 *
 * Please refer to {@see CacheInterface} for common cache operations that are supported by `FileCache`.
 */
final class FileCache implements CacheInterface
{
    private const TTL_INFINITY = 31_536_000; // 1 year
    private const EXPIRATION_EXPIRED = -1;

    /**
     * @var string The cache file suffix. Defaults to '.bin'.
     */
    private string $fileSuffix = '.bin';

    /**
     * @var int|null The permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    private ?int $fileMode = null;

    /**
     * @var int The level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    private int $directoryLevel = 1;

    /**
     * @var int The probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    private int $gcProbability = 10;

    /**
     * @param string $cachePath The directory to store cache files.
     * @param int $directoryMode The permission to be set for newly created directories. This value will be used
     * by PHP `chmod()` function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable
     * by owner and group, but read-only for other users.
     *
     * @see FileCache::$cachePath
     *
     * @throws CacheException If failed to create cache directory.
     */
    public function __construct(
        private string $cachePath,
        private int $directoryMode = 0775,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);

        if (!$this->existsAndNotExpired($file) || ($filePointer = @fopen($file, 'rb')) === false) {
            return $default;
        }

        flock($filePointer, LOCK_SH);
        $value = stream_get_contents($filePointer);
        flock($filePointer, LOCK_UN);
        fclose($filePointer);

        return unserialize($value);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $this->gc();
        $expiration = $this->ttlToExpiration($ttl);

        if ($expiration <= self::EXPIRATION_EXPIRED) {
            return $this->delete($key);
        }

        $file = $this->getCacheFile($key, ensureDirectory: true);

        // If ownership differs, the touch call will fail, so we try to
        // rebuild the file from scratch by deleting it first
        // https://github.com/yiisoft/yii2/pull/16120
        if (function_exists('posix_geteuid') && is_file($file) && fileowner($file) !== posix_geteuid()) {
            @unlink($file);
        }

        if (file_put_contents($file, serialize($value), LOCK_EX) === false) {
            return false;
        }

        if ($this->fileMode !== null) {
            $result = @chmod($file, $this->fileMode);
            if (!$this->isLastErrorSafe($result)) {
                return false;
            }
        }

        $result = false;

        if (@touch($file, $expiration)) {
            clearstatcache();
            $result = true;
        }

        return $this->isLastErrorSafe($result);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $file = $this->getCacheFile($key);

        if (!is_file($file)) {
            return true;
        }

        $result = @unlink($file);

        return $this->isLastErrorSafe($result);
    }

    public function clear(): bool
    {
        $this->removeCacheFiles($this->cachePath, false);
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeys(array_map('\strval', array_keys($values)));

        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keys = $this->iterableToArray($keys);
        $this->validateKeys($keys);

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return $this->existsAndNotExpired($this->getCacheFile($key));
    }

    /**
     * @param string $fileSuffix The cache file suffix. Defaults to '.bin'.
     */
    public function withFileSuffix(string $fileSuffix): self
    {
        $new = clone $this;
        $new->fileSuffix = $fileSuffix;
        return $new;
    }

    /**
     * @param int $fileMode The permission to be set for newly created cache files. This value will be used
     * by PHP `chmod()` function. No umask will be applied. If not set, the permission will be determined
     * by the current environment.
     */
    public function withFileMode(int $fileMode): self
    {
        $new = clone $this;
        $new->fileMode = $fileMode;
        return $new;
    }

    /**
     * @param int $directoryMode The permission to be set for newly created directories. This value will be used
     * by PHP `chmod()` function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable
     * by owner and group, but read-only for other users.
     *
     * @deprecated Use `$directoryMode` in the constructor instead
     */
    public function withDirectoryMode(int $directoryMode): self
    {
        $new = clone $this;
        $new->directoryMode = $directoryMode;
        return $new;
    }

    /**
     * @param int $directoryLevel The level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public function withDirectoryLevel(int $directoryLevel): self
    {
        $new = clone $this;
        $new->directoryLevel = $directoryLevel;
        return $new;
    }

    /**
     * @param int $gcProbability The probability (parts per million) that garbage collection (GC) should
     * be performed when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    public function withGcProbability(int $gcProbability): self
    {
        $new = clone $this;
        $new->gcProbability = $gcProbability;
        return $new;
    }

    /**
     * Converts TTL to expiration.
     */
    private function ttlToExpiration(null|int|string|DateInterval $ttl = null): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            return self::TTL_INFINITY + time();
        }

        if ($ttl <= 0) {
            return self::EXPIRATION_EXPIRED;
        }

        return $ttl + time();
    }

    /**
     * Normalizes cache TTL handling strings and {@see DateInterval} objects.
     *
     * @param DateInterval|int|string|null $ttl The raw TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl(null|int|string|DateInterval $ttl = null): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))
                ->add($ttl)
                ->getTimestamp();
        }

        return (int) $ttl;
    }

    /**
     * Ensures that the directory exists.
     *
     * @param string $path The path to the directory.
     */
    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (is_file($path)) {
            throw new CacheException("Failed to create cache directory, file with the same name exists: \"$path\".");
        }

        set_error_handler(
            static function (int $errorNumber, string $errorString) use ($path): bool {
                if (is_dir($path)) {
                    return true;
                }
                throw new CacheException(
                    sprintf('Failed to create directory "%s". %s', $path, $errorString),
                    $errorNumber,
                );
            }
        );
        try {
            mkdir($path, recursive: true);
        } finally {
            restore_error_handler();
        }

        chmod($path, $this->directoryMode);
    }

    /**
     * Returns the cache file path given the cache key.
     *
     * @param string $key The cache key.
     *
     * @return string The cache file path.
     */
    private function getCacheFile(string $key, bool $ensureDirectory = false): string
    {
        if ($ensureDirectory) {
            $this->ensureDirectory($this->cachePath);
        }

        if ($this->directoryLevel < 1) {
            return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->fileSuffix;
        }

        $base = $this->cachePath;

        for ($i = 0; $i < $this->directoryLevel; ++$i) {
            if (($prefix = substr($key, $i + $i, 2)) !== '') {
                $base .= DIRECTORY_SEPARATOR . $prefix;
                if ($ensureDirectory) {
                    $this->ensureDirectory($base);
                }
            }
        }

        return $base . DIRECTORY_SEPARATOR . $key . $this->fileSuffix;
    }

    /**
     * Recursively removing expired cache files under a directory. This method is mainly used by {@see gc()}.
     *
     * @param string $path The directory under which expired cache files are removed.
     * @param bool $expiredOnly Whether to only remove expired cache files.
     * If false, all files under `$path` will be removed.
     */
    private function removeCacheFiles(string $path, bool $expiredOnly): void
    {
        if (($handle = @opendir($path)) === false) {
            return;
        }

        while (($file = readdir($handle)) !== false) {
            if (str_starts_with($file, '.')) {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                $this->removeCacheFiles($fullPath, $expiredOnly);

                if (!$expiredOnly && !@rmdir($fullPath)) {
                    $errorMessage = error_get_last()['message'] ?? '';
                    throw new CacheException("Unable to remove directory '{$fullPath}': {$errorMessage}");
                }
            } elseif ((!$expiredOnly || @filemtime($fullPath) < time()) && !@unlink($fullPath)) {
                $errorMessage = error_get_last()['message'] ?? '';
                throw new CacheException("Unable to remove file '{$fullPath}': {$errorMessage}");
            }
        }

        closedir($handle);
    }

    /**
     * Removes expired cache files.
     */
    private function gc(): void
    {
        if (random_int(0, 1_000_000) < $this->gcProbability) {
            $this->removeCacheFiles($this->cachePath, true);
        }
    }

    private function validateKey(string $key): void
    {
        if ($key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }

    /**
     * @param string[] $keys
     */
    private function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    private function existsAndNotExpired(string $file): bool
    {
        return is_file($file) && @filemtime($file) > time();
    }

    /**
     * Converts iterable to array.
     *
     * @psalm-template TKey
     * @psalm-template TValue
     * @psalm-param iterable<TKey, TValue> $iterable
     * @psalm-return array<TKey, TValue>
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof Traversable ? iterator_to_array($iterable) : $iterable;
    }

    /**
     * Check if error was because of file was already deleted by another process on high load.
     */
    private function isLastErrorSafe(bool $result): bool
    {
        if ($result !== false) {
            return true;
        }

        $lastError = error_get_last();

        if ($lastError === null) {
            return true;
        }

        if (str_ends_with($lastError['message'] ?? '', 'No such file or directory')) {
            error_clear_last();
            return true;
        }

        return false;
    }
}
