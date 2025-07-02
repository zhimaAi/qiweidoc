<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\Package\BasePackage;

/**
 * @internal
 * @psalm-type PackageConfigurationType = array<string, string|list<string>>
 * @psalm-type EnvironmentsConfigurationType = array<string, array<string, string|string[]>>
 */
final class ConfigSettings
{
    private readonly Options $options;

    /**
     * @psalm-var PackageConfigurationType
     */
    private readonly array $packageConfiguration;

    /**
     * @psalm-var EnvironmentsConfigurationType
     */
    private readonly array $environmentsConfiguration;

    private function __construct(
        private readonly string $path,
        array $composerExtra,
    ) {
        if (isset($composerExtra['config-plugin-file'])) {
            /**
             * @var array $extra
             * @psalm-suppress UnresolvableInclude,MixedOperand
             */
            $extra = require $this->path . '/' . $composerExtra['config-plugin-file'];
        } else {
            $extra = $composerExtra;
        }

        $this->options = new Options($extra);

        /** @psalm-var PackageConfigurationType */
        $this->packageConfiguration = (array) ($extra['config-plugin'] ?? []);

        /** @psalm-var EnvironmentsConfigurationType */
        $this->environmentsConfiguration = $extra['config-plugin-environments'] ?? [];
    }

    public static function forRootPackage(Composer $composer): self
    {
        /** @psalm-suppress PossiblyFalseArgument */
        return new self(
            realpath(dirname(Factory::getComposerFile())),
            $composer->getPackage()->getExtra(),
        );
    }

    public static function forVendorPackage(Composer $composer, BasePackage $package): self
    {
        /**
         * @var string $rootPath Because we use library and composer-plugins only which always has installation path.
         * @see PackagesListBuilder::getAllPackages()
         */
        $rootPath = $composer->getInstallationManager()->getInstallPath($package);
        return new self($rootPath, $package->getExtra());
    }

    public function path(): string
    {
        return $this->path;
    }

    public function configPath(): string
    {
        $sourceDirectory = $this->options->sourceDirectory();
        return $this->path . (empty($sourceDirectory) ? '' : "/$sourceDirectory");
    }

    public function options(): Options
    {
        return $this->options;
    }

    /**
     * Returns the root package configuration.
     *
     * @return array The root package configuration.
     *
     * @psalm-return PackageConfigurationType
     */
    public function packageConfiguration(): array
    {
        return $this->packageConfiguration;
    }

    /**
     * Returns the environments configuration.
     *
     * @return array The environments configuration.
     *
     * @psalm-return EnvironmentsConfigurationType
     */
    public function environmentsConfiguration(): array
    {
        return $this->environmentsConfiguration;
    }
}
