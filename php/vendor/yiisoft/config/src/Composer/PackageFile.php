<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

/**
 * @internal
 */
final class PackageFile
{
    public function __construct(
        private string $filename,
        private string $relativePath,
        private string $absolutePath,
    ) {
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function absolutePath(): string
    {
        return $this->absolutePath;
    }
}
