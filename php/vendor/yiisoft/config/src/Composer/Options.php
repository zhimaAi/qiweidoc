<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use function is_array;
use function is_string;
use function str_replace;
use function trim;

/**
 * @internal
 */
final class Options
{
    public const DEFAULT_MERGE_PLAN_FILE = '.merge-plan.php';
    public const DEFAULT_CONFIG_DIRECTORY = '';
    public const DEFAULT_VENDOR_DIRECTORY = 'vendor';
    public const DEFAULT_ENVIRONMENT = '/';
    public const ROOT_PACKAGE_NAME = '/';
    public const VENDOR_OVERRIDE_PACKAGE_NAME = '//';
    public const DEFAULT_PACKAGE_TYPES = ['library', 'composer-plugin'];

    private string $mergePlanFile = self::DEFAULT_MERGE_PLAN_FILE;
    private bool $buildMergePlan = true;

    /**
     * @var string[]
     */
    private array $vendorOverrideLayerPackages = [];

    private string $sourceDirectory = self::DEFAULT_CONFIG_DIRECTORY;

    /**
     * @var string[]
     */
    private array $packageTypes = self::DEFAULT_PACKAGE_TYPES;

    public function __construct(array $extra)
    {
        if (!isset($extra['config-plugin-options']) || !is_array($extra['config-plugin-options'])) {
            return;
        }

        $options = $extra['config-plugin-options'];

        if (!empty($options['merge-plan-file'])) {
            $this->mergePlanFile = (string) $options['merge-plan-file'];
        }

        if (isset($options['build-merge-plan'])) {
            $this->buildMergePlan = (bool) $options['build-merge-plan'];
        }

        if (isset($options['vendor-override-layer'])) {
            $this->vendorOverrideLayerPackages = array_filter(
                (array) $options['vendor-override-layer'],
                static fn(mixed $value): bool => is_string($value),
            );
        }

        if (isset($options['source-directory'])) {
            $this->sourceDirectory = $this->normalizePath((string) $options['source-directory']);
        }

        if (isset($options['package-types'])) {
            $this->packageTypes = array_filter(
                (array) $options['package-types'],
                static fn(mixed $value): bool => is_string($value),
            );
        }
    }

    public static function containsWildcard(string $file): bool
    {
        return str_contains($file, '*');
    }

    public static function isOptional(string $file): bool
    {
        return str_starts_with($file, '?');
    }

    public static function isVariable(string $file): bool
    {
        return str_starts_with($file, '$');
    }

    public function mergePlanFile(): string
    {
        return $this->mergePlanFile;
    }

    public function buildMergePlan(): bool
    {
        return $this->buildMergePlan;
    }

    /**
     * @return string[]
     */
    public function vendorOverrideLayerPackages(): array
    {
        return $this->vendorOverrideLayerPackages;
    }

    public function sourceDirectory(): string
    {
        return $this->sourceDirectory;
    }

    /**
     * @return string[]
     */
    public function packageTypes(): array
    {
        return $this->packageTypes;
    }

    private function normalizePath(string $value): string
    {
        return trim(str_replace('\\', '/', $value), '/');
    }
}
