<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use Yiisoft\Config\Composer\Options;

/**
 * @internal
 */
final class MergePlan
{
    /**
     * @psalm-param array<string, array<string, array<string, string[]>>> $mergePlan
     */
    public function __construct(
        private array $mergePlan = [],
    ) {
    }

    /**
     * Adds an item to the merge plan.
     *
     * @param string $file The config file.
     * @param string $package The package name.
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function add(
        string $file,
        string $package,
        string $group,
        string $environment = Options::DEFAULT_ENVIRONMENT
    ): void {
        $this->mergePlan[$environment][$group][$package][] = $file;
    }

    /**
     * Adds a multiple items to the merge plan.
     *
     * @param string[] $files The config files.
     * @param string $package The package name.
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function addMultiple(
        array $files,
        string $package,
        string $group,
        string $environment = Options::DEFAULT_ENVIRONMENT
    ): void {
        $this->mergePlan[$environment][$group][$package] = $files;
    }

    /**
     * Adds an empty environment item to the merge plan.
     *
     * @param string $environment The environment name.
     */
    public function addEnvironmentWithoutConfigs(string $environment): void
    {
        $this->mergePlan[$environment] = [];
    }

    /**
     * Add empty group if it not exists.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     */
    public function addGroup(string $group, string $environment = Options::DEFAULT_ENVIRONMENT): void
    {
        if (!isset($this->mergePlan[$environment][$group])) {
            $this->mergePlan[$environment][$group] = [];
        }
    }

    /**
     * Returns the merge plan group.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @return array<string, string[]>
     */
    public function getGroup(string $group, string $environment = Options::DEFAULT_ENVIRONMENT): array
    {
        return $this->mergePlan[$environment][$group] ?? [];
    }

    /**
     * Returns the merge plan as an array.
     *
     * @psalm-return array<string, array<string, array<string, string[]>>>
     */
    public function toArray(): array
    {
        return $this->mergePlan;
    }

    /**
     * Checks whether the group exists in the merge plan.
     *
     * @param string $group The group name.
     * @param string $environment The environment name.
     *
     * @return bool Whether the group exists in the merge plan.
     */
    public function hasGroup(string $group, string $environment): bool
    {
        return isset($this->mergePlan[$environment][$group]);
    }

    /**
     * Checks whether the environment exists in the merge plan.
     *
     * @param string $environment The environment name.
     *
     * @return bool Whether the environment exists in the merge plan.
     */
    public function hasEnvironment(string $environment): bool
    {
        return isset($this->mergePlan[$environment]);
    }
}
