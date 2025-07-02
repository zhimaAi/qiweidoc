<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

use InvalidArgumentException;

/**
 * @see RemoveFromVendor::keys()
 */
final class RemoveKeysFromVendor
{
    /**
     * @var string[][]
     */
    private array $keys;

    /**
     * @var string[][]
     */
    private array $packages = [];

    /**
     * @param string[] ...$keys
     */
    public function __construct(array ...$keys)
    {
        $this->keys = $keys;
    }

    public function package(string ...$package): self
    {
        if ($package === []) {
            throw new InvalidArgumentException('Package should be in format "packageName[, group][, group]".');
        }
        $this->packages[] = $package;
        return $this;
    }

    /**
     * @return string[][]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @return string[][]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }
}
