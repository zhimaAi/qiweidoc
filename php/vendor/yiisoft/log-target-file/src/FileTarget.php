<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Target;

use function chmod;
use function clearstatcache;
use function dirname;
use function error_get_last;
use function fclose;
use function file_exists;
use function file_put_contents;
use function flock;
use function fwrite;
use function sprintf;
use function strlen;

use const FILE_APPEND;
use const LOCK_EX;
use const LOCK_UN;

/**
 * FileTarget records log messages in a file.
 *
 * The log file is specified via {@see FileTarget::$logFile}.
 *
 * If {@see FileRotator} is used and the size of the log file exceeds {@see FileRotator::$maxFileSize},
 * a rotation will be performed, which renames the current log file by suffixing the file name with '.1'.
 * All existing log files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * If compression is enabled {@see FileRotator::$compressRotatedFiles}, the rotated files will be compressed
 * into the '.gz' format. The property {@see FileRotator::$maxFiles} specifies how many history files to keep.
 */
final class FileTarget extends Target
{
    /**
     * @param string $logFile The log file path. If not set, it will use the "/tmp/app.log" file. The directory
     * containing the log files will be automatically created if not existing.
     * @param FileRotatorInterface|null $rotator The instance that takes care of rotating files.
     * @param int $dirMode The permission to be set for newly created directories. This value will be used by PHP
     * `chmod()` function. No umask will be applied. Defaults to 0775, meaning the directory is read-writable by owner
     * and group, but read-only for other users.
     * @param int|null $fileMode The permission to be set for newly created log files. This value will be used by PHP
     * `chmod()` function. No umask will be applied. If not set, the permission will be determined by the current
     * environment.
     */
    public function __construct(
        private string $logFile = '/tmp/app.log',
        private ?FileRotatorInterface $rotator = null,
        private int $dirMode = 0775,
        private ?int $fileMode = null
    ) {
        parent::__construct();
    }

    protected function export(): void
    {
        $logPath = dirname($this->logFile);

        if (!file_exists($logPath)) {
            FileHelper::ensureDirectory($logPath, $this->dirMode);
        }

        $text = $this->formatMessages("\n");
        $filePointer = FileHelper::openFile($this->logFile, 'ab');
        flock($filePointer, LOCK_EX);

        if ($this->rotator !== null) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }

        if ($this->rotator !== null && $this->rotator->shouldRotateFile($this->logFile)) {
            flock($filePointer, LOCK_UN);
            fclose($filePointer);
            $this->rotator->rotateFile($this->logFile);
            $writeResult = file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            $writeResult = fwrite($filePointer, $text);
            flock($filePointer, LOCK_UN);
            fclose($filePointer);
        }

        $this->checkWrittenResult($writeResult, $text);

        if ($this->fileMode !== null) {
            chmod($this->logFile, $this->fileMode);
        }
    }

    /**
     * Checks the written result.
     *
     * @param false|int $writeResult The number of bytes written to the file, or FALSE if an error occurs.
     * @param string $text The text written to the file.
     *
     * @throws RuntimeException For unable to export log through file.
     */
    private function checkWrittenResult(false|int $writeResult, string $text): void
    {
        if ($writeResult === false) {
            throw new RuntimeException(sprintf(
                'Unable to export log through file: %s',
                error_get_last()['message'] ?? '',
            ));
        }

        $textSize = strlen($text);

        if ($writeResult < $textSize) {
            throw new RuntimeException(sprintf(
                'Unable to export whole log through file. Wrote %d out of %d bytes.',
                $writeResult,
                $textSize,
            ));
        }
    }
}
