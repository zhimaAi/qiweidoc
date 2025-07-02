<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Files\FileHelper;

use function chmod;
use function extension_loaded;
use function fclose;
use function feof;
use function file_exists;
use function filesize;
use function flock;
use function fread;
use function ftruncate;
use function is_file;
use function rename;
use function sprintf;
use function substr;
use function unlink;

use const LOCK_EX;
use const LOCK_SH;
use const LOCK_UN;

/**
 * FileRotator takes care of rotating files.
 *
 * If the size of the file exceeds {@see FileRotator::$maxFileSize} (in kilo-bytes),
 * a rotation will be performed, which renames the current file by suffixing the file name with '.1'.
 *
 * All existing files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on. If compression
 * is enabled {@see FileRotator::$compressRotatedFiles}, the rotated files will be compressed into the '.gz' format.
 * The property {@see FileRotator::$maxFiles} specifies how many history files to keep.
 */
final class FileRotator implements FileRotatorInterface
{
    /**
     * The extension of the compressed rotated files.
     */
    private const COMPRESS_EXTENSION = '.gz';

    /**
     * @var int The maximum file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    private int $maxFileSize;

    /**
     * @var int The number of files used for rotation. Defaults to 5.
     */
    private int $maxFiles;

    /**
     * @var bool Whether to compress rotated files with gzip. Defaults to `false`.
     *
     * If compression is enabled, the rotated files will be compressed into the '.gz' format.
     */
    private bool $compressRotatedFiles;

    /**
     * @param int $maxFileSize The maximum file size, in kilobytes. Defaults to 10240, meaning 10MB.
     * @param int $maxFiles The number of files used for rotation. Defaults to 5.
     * @param int|null $fileMode The permission to be set for newly created files. This value will be used by PHP
     * `chmod()` function. No umask will be applied. If not set, the permission will be determined by the current
     * environment.
     * @param bool $compressRotatedFiles Whether to compress rotated files with gzip.
     */
    public function __construct(
        int $maxFileSize = 10240,
        int $maxFiles = 5,
        private ?int $fileMode = null,
        bool $compressRotatedFiles = false
    ) {
        $this->checkCannotBeLowerThanOne($maxFileSize, '$maxFileSize');
        $this->checkCannotBeLowerThanOne($maxFiles, '$maxFiles');

        $this->maxFileSize = $maxFileSize;
        $this->maxFiles = $maxFiles;

        if ($compressRotatedFiles && !extension_loaded('zlib')) {
            throw new RuntimeException(sprintf(
                'The %s requires the PHP extension "ext-zlib" to compress rotated files.',
                self::class,
            ));
        }

        $this->compressRotatedFiles = $compressRotatedFiles;
    }

    public function rotateFile(string $file): void
    {
        for ($i = $this->maxFiles; $i >= 0; --$i) {
            // `$i === 0` is the original file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            $newFile = $file . '.' . ($i + 1);

            if ($i === $this->maxFiles) {
                $this->safeRemove($this->compressRotatedFiles ? $rotateFile . self::COMPRESS_EXTENSION : $rotateFile);
                continue;
            }

            if ($this->compressRotatedFiles && is_file($rotateFile . self::COMPRESS_EXTENSION)) {
                $this->rotate($rotateFile . self::COMPRESS_EXTENSION, $newFile . self::COMPRESS_EXTENSION);
                continue;
            }

            if (!is_file($rotateFile)) {
                continue;
            }

            $this->rotate($rotateFile, $newFile);

            if ($i === 0) {
                $this->clear($rotateFile);
            }
        }
    }

    public function shouldRotateFile(string $file): bool
    {
        return file_exists($file) && @filesize($file) > ($this->maxFileSize * 1024);
    }

    /***
     * Renames rotated file into new file.
     *
     * @param string $rotateFile
     * @param string $newFile
     */
    private function rotate(string $rotateFile, string $newFile): void
    {
        $this->safeRemove($newFile);
        rename($rotateFile, $newFile);

        if ($this->compressRotatedFiles && !$this->isCompressed($newFile)) {
            $this->compress($newFile);
            $newFile .= self::COMPRESS_EXTENSION;
        }

        if ($this->fileMode !== null) {
            chmod($newFile, $this->fileMode);
        }
    }

    /**
     * Compresses a file with gzip and renames it by appending `.gz` to the file.
     */
    private function compress(string $file): void
    {
        $filePointer = FileHelper::openFile($file, 'rb');
        flock($filePointer, LOCK_SH);
        $gzFile = $file . self::COMPRESS_EXTENSION;
        $gzFilePointer = gzopen($gzFile, 'wb9');

        while (!feof($filePointer)) {
            gzwrite($gzFilePointer, fread($filePointer, 8192));
        }

        flock($filePointer, LOCK_UN);
        fclose($filePointer);
        gzclose($gzFilePointer);
        @unlink($file);
    }

    /***
     * Clears the file without closing any other process open handles.
     *
     * @param string $file
     */
    private function clear(string $file): void
    {
        $filePointer = FileHelper::openFile($file, 'ab');

        flock($filePointer, LOCK_EX);
        ftruncate($filePointer, 0);
        flock($filePointer, LOCK_UN);
        fclose($filePointer);
    }

    /**
     * Checks the existence of file and removes it.
     */
    private function safeRemove(string $file): void
    {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Whether the file is compressed.
     */
    private function isCompressed(string $file): bool
    {
        return substr($file, -3, 3) === self::COMPRESS_EXTENSION;
    }

    /**
     * Checks that the value cannot be lower than one.
     *
     * @param int $value The value to be checked.
     * @param string $argumentName The name of the argument to check.
     */
    private function checkCannotBeLowerThanOne(int $value, string $argumentName): void
    {
        if ($value < 1) {
            throw new InvalidArgumentException(sprintf(
                'The argument "%s" cannot be lower than 1.',
                $argumentName,
            ));
        }
    }
}
