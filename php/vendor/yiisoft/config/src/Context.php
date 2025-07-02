<?php

declare(strict_types=1);

namespace Yiisoft\Config;

/**
 * @internal
 */
final class Context
{
    public const VENDOR = 1;
    public const VENDOR_OVERRIDE = 2;
    public const APPLICATION = 3;
    public const ENVIRONMENT = 4;

    private string $originalGroup = '';

    public function __construct(
        private readonly string $group,
        private readonly string $package,
        private readonly int $layer,
        private readonly string $file,
        private readonly bool $isVariable,
    ) {
    }

    public function setOriginalGroup(string $group): self
    {
        $this->originalGroup = $group;
        return $this;
    }

    public function originalGroup(): string
    {
        return $this->originalGroup;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function layer(): int
    {
        return $this->layer;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function isVariable(): bool
    {
        return $this->isVariable;
    }
}
