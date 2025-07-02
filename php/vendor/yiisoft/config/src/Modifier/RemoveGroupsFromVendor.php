<?php

declare(strict_types=1);

namespace Yiisoft\Config\Modifier;

/**
 * @see RemoveFromVendor::groups()
 */
final class RemoveGroupsFromVendor
{
    /**
     * @psalm-var array<string, string[]>
     */
    private array $groups = [];

    /**
     * @psalm-param array<string, string|string[]> $groups
     */
    public function __construct(array $groups)
    {
        foreach ($groups as $package => $groupNames) {
            $this->groups[$package] = (array) $groupNames;
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
