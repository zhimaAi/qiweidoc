<?php

declare(strict_types=1);

namespace Yiisoft\Profiler\Target;

use Yiisoft\Files\FileHelper;
use Yiisoft\Profiler\Message;

use function dirname;

/**
 * FileTarget records profiling messages in a file specified via {@see FileTarget::$filePath}.
 */
final class FileTarget extends AbstractTarget
{
    /**
     * @param string $filePath Path of the file to write to. It may contain the placeholders,
     * which will be replaced by computed values. The supported placeholders are:
     *
     * - '{ts}' - profiling completion timestamp.
     * - '{date}' - profiling completion date in format 'ymd'.
     * - '{time}' - profiling completion time in format 'His'.
     *
     * The directory containing the file will be automatically created if not existing.
     * If target file is already exist it will be overridden.
     * @param float $requestBeginTime Time of the beginning of the request. Can be set as `microtime(true)` or
     * `$_SERVER['REQUEST_TIME_FLOAT']` in config.
     * @param int $directoryMode The permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public function __construct(
        private string $filePath,
        private float $requestBeginTime,
        private int $directoryMode = 0775
    ) {
    }

    public function export(array $messages): void
    {
        $memoryPeakUsage = memory_get_peak_usage();

        $totalTime = microtime(true) - $this->requestBeginTime;
        $text = "Total processing time: $totalTime ms; Peak memory: $memoryPeakUsage B. \n\n";

        $text .= implode("\n", array_map([$this, 'formatMessage'], $messages));

        $filename = $this->resolveFilename();

        if (file_exists($filename)) {
            FileHelper::unlink($filename);
        } else {
            $filePath = dirname($filename);

            if (!is_dir($filePath)) {
                FileHelper::ensureDirectory($filePath, $this->directoryMode);
            }
        }

        file_put_contents($filename, $text);
    }

    /**
     * Resolves value of {@see filename} processing path alias and placeholders.
     *
     * @return string Actual target filename.
     */
    private function resolveFilename(): string
    {
        return preg_replace_callback(
            '/{\\w+}/',
            static fn (array $matches) => match ($matches[0]) {
                '{ts}' => (string)time(),
                '{date}' => gmdate('ymd'),
                '{time}' => gmdate('His'),
                default => $matches[0],
            },
            $this->filePath
        );
    }

    /**
     * Formats a profiling message for display as a string.
     *
     * @param Message $message Profiling message to be formatted.
     * The message structure follows that in {@see Profiler::$messages}.
     *
     * @return string Formatted message.
     */
    private function formatMessage(Message $message): string
    {
        return date('Y-m-d H:i:s', (int)$message->context('beginTime'))
            . " [{$message->context('duration')} ms][{$message->context('memoryDiff')} B][{$message->level()}] {$message->token()}";
    }
}
