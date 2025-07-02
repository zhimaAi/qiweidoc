<?php

declare(strict_types=1);

namespace Yiisoft\Files;

use FilesystemIterator;
use InvalidArgumentException;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Yiisoft\Files\PathMatcher\PathMatcherInterface;

use function array_key_exists;
use function filemtime;
use function get_debug_type;
use function is_file;
use function is_string;

/**
 * Provides useful methods to manage files and directories.
 */
final class FileHelper
{
    /**
     * Opens a file or URL.
     *
     * This method is similar to the PHP {@see fopen()} function, except that it suppresses the {@see E_WARNING}
     * level error and throws the {@see RuntimeException} exception if it can't open the file.
     *
     * @param string $filename The file or URL.
     * @param string $mode The type of access.
     * @param bool $useIncludePath Whether to search for a file in the include path.
     * @param resource|null $context The stream context or `null`.
     *
     * @throws RuntimeException If the file could not be opened.
     *
     * @return resource The file pointer resource.
     *
     * @psalm-suppress PossiblyNullArgument
     */
    public static function openFile(string $filename, string $mode, bool $useIncludePath = false, $context = null)
    {
        /** @psalm-suppress InvalidArgument, MixedArgumentTypeCoercion */
        set_error_handler(static function (int $errorNumber, string $errorString) use ($filename): bool {
            throw new RuntimeException(
                sprintf('Failed to open a file "%s". ', $filename) . $errorString,
                $errorNumber
            );
        });

        $filePointer = fopen($filename, $mode, $useIncludePath, $context);

        restore_error_handler();

        if ($filePointer === false) {
            throw new RuntimeException(sprintf('Failed to open a file "%s". ', $filename));
        }

        return $filePointer;
    }

    /**
     * Ensures directory exists and has specific permissions.
     *
     * This method is similar to the PHP {@see mkdir()} function with some differences:
     *
     * - It does not fail if directory already exists.
     * - It uses {@see chmod()} to set the permission of the created directory in order to avoid the impact
     *   of the `umask` setting.
     * - It throws exceptions instead of returning false and emitting {@see E_WARNING}.
     *
     * @param string $path Path of the directory to be created.
     * @param int $mode The permission to be set for the created directory.
     */
    public static function ensureDirectory(string $path, int $mode = 0775): void
    {
        $path = self::normalizePath($path);

        if (!is_dir($path)) {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($path): bool {
                // Handle race condition.
                // See https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
                if (!is_dir($path)) {
                    throw new RuntimeException(
                        sprintf('Failed to create directory "%s". ', $path) . $errorString,
                        $errorNumber
                    );
                }
                return true;
            });

            mkdir($path, $mode, true);

            restore_error_handler();
        }

        if (!chmod($path, $mode)) {
            throw new RuntimeException(sprintf('Unable to set mode "%s" for "%s".', $mode, $path));
        }
    }

    /**
     * Normalizes a file/directory path.
     *
     * The normalization does the following work:
     *
     * - Convert all directory separators into `/` (e.g. "\a/b\c" becomes "/a/b/c")
     * - Remove trailing directory separators (e.g. "/a/b/c/" becomes "/a/b/c")
     * - Turn multiple consecutive slashes into a single one (e.g. "/a///b/c" becomes "/a/b/c")
     * - Remove ".." and "." based on their meanings (e.g. "/a/./b/../c" becomes "/a/c")
     *
     * @param string $path The file/directory path to be normalized.
     *
     * @return string The normalized file/directory path.
     */
    public static function normalizePath(string $path): string
    {
        $isWindowsShare = str_starts_with($path, '\\\\');

        if ($isWindowsShare) {
            $path = substr($path, 2);
        }

        $path = rtrim(strtr($path, '/\\', '//'), '/');

        if (!str_contains('/' . $path, '/.') && !str_contains($path, '//')) {
            return $isWindowsShare ? "\\\\$path" : $path;
        }

        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part !== '.' && ($part !== '' || empty($parts))) {
                $parts[] = $part;
            }
        }

        $path = implode('/', $parts);

        if ($isWindowsShare) {
            $path = '\\\\' . $path;
        }

        return $path === '' ? '.' : $path;
    }

    /**
     * Removes a directory (and all its content) recursively. Does nothing if directory does not exist.
     *
     * @param string $directory The directory to be deleted recursively.
     * @param array $options Options for directory remove ({@see clearDirectory()}).
     *
     * @throw RuntimeException when unable to remove directory.
     */
    public static function removeDirectory(string $directory, array $options = []): void
    {
        if (!file_exists($directory)) {
            return;
        }

        self::clearDirectory($directory, $options);

        if (is_link($directory)) {
            self::unlink($directory);
        } else {
            set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): bool {
                throw new RuntimeException(
                    sprintf('Failed to remove directory "%s". ', $directory) . $errorString,
                    $errorNumber
                );
            });

            rmdir($directory);

            restore_error_handler();
        }
    }

    /**
     * Clears all directory content.
     *
     * @param string $directory The directory to be cleared.
     * @param array $options Options for directory clear. Valid options are:
     *
     * - traverseSymlinks: boolean, whether symlinks to the directories should be traversed too.
     *   Defaults to `false`, meaning the content of the symlinked directory would not be deleted.
     *   Only symlink would be removed in that default case.
     *
     * @throws RuntimeException if unable to open directory.
     */
    public static function clearDirectory(string $directory, array $options = []): void
    {
        $handle = self::openDirectory($directory);
        if (!empty($options['traverseSymlinks']) || !is_link($directory)) {
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $directory . '/' . $file;
                if (is_dir($path)) {
                    self::removeDirectory($path, $options);
                } else {
                    self::unlink($path);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Removes a file or symlink in a cross-platform way.
     *
     * @param string $path Path to unlink.
     */
    public static function unlink(string $path): void
    {
        /** @psalm-suppress InvalidArgument, MixedArgumentTypeCoercion */
        set_error_handler(static function (int $errorNumber, string $errorString) use ($path): bool {
            throw new RuntimeException(
                sprintf('Failed to unlink "%s". ', $path) . $errorString,
                $errorNumber
            );
        });

        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if ($isWindows) {
            if (is_link($path)) {
                try {
                    unlink($path);
                } catch (RuntimeException) {
                    rmdir($path);
                }
            } else {
                if (file_exists($path) && !is_writable($path)) {
                    chmod($path, 0777);
                }
                unlink($path);
            }
        } else {
            unlink($path);
        }
        restore_error_handler();
    }

    /**
     * Tells whether the path is an empty directory.
     *
     * @param string $path Path to check for being an empty directory.
     *
     * @return bool
     */
    public static function isEmptyDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        return !(new FilesystemIterator($path))->valid();
    }

    /**
     * Copies a whole directory as another one.
     *
     * The files and subdirectories will also be copied over.
     *
     * @param string $source The source directory.
     * @param string $destination The destination directory.
     * @param array $options Options for directory copy. Valid options are:
     *
     * - dirMode: integer, the permission to be set for newly copied directories. Defaults to 0775.
     * - fileMode: integer, the permission to be set for newly copied files. Defaults to the current environment
     *   setting.
     * - filter: a filter to apply while copying files. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the files under the subdirectories should also be copied. Defaults to true.
     * - beforeCopy: callback, a PHP callback that is called before copying each subdirectory or file. If the callback
     *   returns false, the copy operation for the subdirectory or file will be cancelled. The signature of the
     *   callback should be: `function ($from, $to)`, where `$from` is the subdirectory or file to be copied from,
     *   while `$to` is the copy target.
     * - afterCopy: callback, a PHP callback that is called after each subdirectory or file is successfully copied.
     *   The signature of the callback should be: `function ($from, $to)`, where `$from` is the subdirectory or file
     *   copied from, while `$to` is the copy target.
     * - copyEmptyDirectories: boolean, whether to copy empty directories. Set this to false to avoid creating
     *   directories that do not contain files. This affects directories that do not contain files initially as well as
     *   directories that do not contain files at the target destination because files have been filtered via `only` or
     *   `except`. Defaults to true.
     *
     * @throws RuntimeException if unable to open directory
     *
     * @psalm-param array{
     *   dirMode?: int,
     *   fileMode?: int,
     *   filter?: PathMatcherInterface|mixed,
     *   recursive?: bool,
     *   beforeCopy?: callable,
     *   afterCopy?: callable,
     *   copyEmptyDirectories?: bool,
     * } $options
     */
    public static function copyDirectory(string $source, string $destination, array $options = []): void
    {
        $filter = self::getFilter($options);
        $afterCopy = $options['afterCopy'] ?? null;
        $beforeCopy = $options['beforeCopy'] ?? null;
        $recursive = !array_key_exists('recursive', $options) || $options['recursive'];

        if (!isset($options['dirMode'])) {
            $options['dirMode'] = 0755;
        }

        $source = self::normalizePath($source);
        $destination = self::normalizePath($destination);
        $copyEmptyDirectories = !array_key_exists('copyEmptyDirectories', $options) || $options['copyEmptyDirectories'];

        self::assertNotSelfDirectory($source, $destination);

        if (self::processCallback($beforeCopy, $source, $destination) === false) {
            return;
        }

        if ($copyEmptyDirectories && !is_dir($destination)) {
            self::ensureDirectory($destination, $options['dirMode']);
        }

        $handle = self::openDirectory($source);

        if (!array_key_exists('basePath', $options)) {
            $options['basePath'] = realpath($source);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $source . '/' . $file;
            $to = $destination . '/' . $file;

            if ($filter === null || $filter->match($from)) {
                if (is_file($from)) {
                    self::copyFile($from, $to, $options);
                } elseif ($recursive) {
                    self::copyDirectory($from, $to, $options);
                }
            }
        }

        closedir($handle);

        self::processCallback($afterCopy, $source, $destination);
    }

    /**
     * Copies files with some options.
     *
     * - dirMode: integer or null, the permission to be set for newly copied directories. Defaults to null.
     *   When null - directory will be not created
     * - fileMode: integer, the permission to be set for newly copied files. Defaults to the current environment
     *   setting.
     * - beforeCopy: callback, a PHP callback that is called before copying file. If the callback
     *   returns false, the copy operation for file will be cancelled. The signature of the
     *   callback should be: `function ($from, $to)`, where `$from` is the file to be copied from,
     *   while `$to` is the copy target.
     * - afterCopy: callback, a PHP callback that is called after file if successfully copied.
     *   The signature of the callback should be: `function ($from, $to)`, where `$from` is the file
     *   copied from, while `$to` is the copy target.
     *
     * @param string $source The source file
     * @param string $destination The destination filename
     * @param array $options
     *
     * @psalm-param array{
     *   dirMode?: int,
     *   fileMode?: int,
     *   beforeCopy?: callable,
     *   afterCopy?: callable,
     * } $options
     */
    public static function copyFile(string $source, string $destination, array $options = []): void
    {
        if (!is_file($source)) {
            throw new InvalidArgumentException('Argument $source must be an existing file.');
        }

        $dirname = dirname($destination);
        $dirMode = $options['dirMode'] ?? 0755;
        $fileMode = $options['fileMode'] ?? null;
        $afterCopy = $options['afterCopy'] ?? null;
        $beforeCopy = $options['beforeCopy'] ?? null;

        if (self::processCallback($beforeCopy, $source, $destination) === false) {
            return;
        }

        if (!is_dir($dirname)) {
            self::ensureDirectory($dirname, $dirMode);
        }

        if (!copy($source, $destination)) {
            throw new RuntimeException('Failed to copy the file.');
        }

        if ($fileMode !== null && !chmod($destination, $fileMode)) {
            throw new RuntimeException(sprintf('Unable to set mode "%s" for "%s".', $fileMode, $destination));
        }

        self::processCallback($afterCopy, $source, $destination);
    }

    /**
     * @param callable|null $callback
     * @param array $arguments
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    private static function processCallback(?callable $callback, mixed ...$arguments): mixed
    {
        return $callback ? $callback(...$arguments) : null;
    }

    private static function getFilter(array $options): ?PathMatcherInterface
    {
        if (!array_key_exists('filter', $options)) {
            return null;
        }

        if (!$options['filter'] instanceof PathMatcherInterface) {
            $type = get_debug_type($options['filter']);
            throw new InvalidArgumentException(
                sprintf('Filter should be an instance of PathMatcherInterface, %s given.', $type)
            );
        }

        return $options['filter'];
    }

    /**
     * Assert that destination is not within the source directory.
     *
     * @param string $source Path to source.
     * @param string $destination Path to destination.
     *
     * @throws InvalidArgumentException
     */
    private static function assertNotSelfDirectory(string $source, string $destination): void
    {
        if ($source === $destination || str_starts_with($destination, $source . '/')) {
            throw new InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }
    }

    /**
     * Open directory handle.
     *
     * @param string $directory Path to directory.
     *
     * @throws RuntimeException if unable to open directory.
     * @throws InvalidArgumentException if argument is not a directory.
     *
     * @return resource
     */
    private static function openDirectory(string $directory)
    {
        if (!file_exists($directory)) {
            throw new InvalidArgumentException("\"$directory\" does not exist.");
        }

        if (!is_dir($directory)) {
            throw new InvalidArgumentException("\"$directory\" is not a directory.");
        }

        /** @psalm-suppress InvalidArgument, MixedArgumentTypeCoercion */
        set_error_handler(static function (int $errorNumber, string $errorString) use ($directory): bool {
            throw new RuntimeException(
                sprintf('Unable to open directory "%s". ', $directory) . $errorString,
                $errorNumber
            );
        });

        $handle = opendir($directory);

        if ($handle === false) {
            throw new RuntimeException(sprintf('Unable to open directory "%s". ', $directory));
        }

        restore_error_handler();

        return $handle;
    }

    /**
     * Returns the last modification time for the given paths.
     *
     * If the path is a directory, any nested files/directories will be checked as well.
     *
     * @param RecursiveDirectoryIterator[]|string[] $paths The directories to be checked.
     *
     * @throws LogicException If path is not set.
     *
     * @return int|null Unix timestamp representing the last modification time.
     */
    public static function lastModifiedTime(string|RecursiveDirectoryIterator ...$paths): ?int
    {
        if (empty($paths)) {
            throw new LogicException('Path is required.');
        }

        $time = null;

        foreach ($paths as $path) {
            if (is_string($path)) {
                $timestamp = self::modifiedTime($path);

                if ($timestamp > $time) {
                    $time = $timestamp;
                }

                if (is_file($path)) {
                    continue;
                }

                $path = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
            }

            /** @var iterable<string, string> $iterator */
            $iterator = new RecursiveIteratorIterator(
                $path,
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $path => $_info) {
                $timestamp = self::modifiedTime($path);

                if ($timestamp > $time) {
                    $time = $timestamp;
                }
            }
        }

        return $time;
    }

    private static function modifiedTime(string $path): ?int
    {
        if (false !== $timestamp = filemtime($path)) {
            return $timestamp;
        }

        return null;
    }

    /**
     * Returns the directories found under the specified directory and subdirectories.
     *
     * @param string $directory The directory under which the files will be looked for.
     * @param array $options Options for directory searching. Valid options are:
     *
     * - filter: a filter to apply while looked directories. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the subdirectories should also be looked for. Defaults to `true`.
     *
     * @psalm-param array{
     *   filter?: PathMatcherInterface|mixed,
     *   recursive?: bool,
     * } $options
     *
     * @throws InvalidArgumentException If the directory is invalid.
     *
     * @return string[] Directories found under the directory specified, in no particular order.
     * Ordering depends on the file system used.
     */
    public static function findDirectories(string $directory, array $options = []): array
    {
        $filter = self::getFilter($options);
        $recursive = !array_key_exists('recursive', $options) || $options['recursive'];
        $directory = self::normalizePath($directory);

        $result = [];

        $handle = self::openDirectory($directory);
        while (false !== $file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;
            if (is_file($path)) {
                continue;
            }

            if ($filter === null || $filter->match($path)) {
                $result[] = $path;
            }

            if ($recursive) {
                $result = array_merge($result, self::findDirectories($path, $options));
            }
        }
        closedir($handle);

        return $result;
    }

    /**
     * Returns the files found under the specified directory and subdirectories.
     *
     * @param string $directory The directory under which the files will be looked for.
     * @param array $options Options for file searching. Valid options are:
     *
     * - filter: a filter to apply while looked files. It should be an instance of {@see PathMatcherInterface}.
     * - recursive: boolean, whether the files under the subdirectories should also be looked for. Defaults to `true`.
     *
     * @psalm-param array{
     *   filter?: PathMatcherInterface|mixed,
     *   recursive?: bool,
     * } $options
     *
     * @throws InvalidArgumentException If the directory is invalid.
     *
     * @return string[] Files found under the directory specified, in no particular order.
     * Ordering depends on the files system used.
     */
    public static function findFiles(string $directory, array $options = []): array
    {
        $filter = self::getFilter($options);
        $recursive = !array_key_exists('recursive', $options) || $options['recursive'];

        $directory = self::normalizePath($directory);

        $result = [];

        $handle = self::openDirectory($directory);
        while (false !== $file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_file($path)) {
                if ($filter === null || $filter->match($path)) {
                    $result[] = $path;
                }
                continue;
            }

            if ($recursive) {
                $result = array_merge($result, self::findFiles($path, $options));
            }
        }
        closedir($handle);

        return $result;
    }
}
