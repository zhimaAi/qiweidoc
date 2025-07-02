<?php

declare(strict_types=1);

namespace Yiisoft\Log\Target\File;

/**
 * Interface for FileRotator
 */
interface FileRotatorInterface
{
    /**
     * Rotates files.
     *
     * @param string $file The log file for rotation.
     */
    public function rotateFile(string $file): void;

    /**
     * Checks whether the file should be rotated.
     *
     * @param string $file The log file for rotation.
     *
     * @return bool Whether you should rotate the file.
     */
    public function shouldRotateFile(string $file): bool;
}
