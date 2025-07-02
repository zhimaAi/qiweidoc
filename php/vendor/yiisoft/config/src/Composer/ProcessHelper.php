<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Strings\WildcardPattern;

use function str_replace;

/**
 * @internal
 * @psalm-import-type PackageConfigurationType from ConfigSettings
 * @psalm-import-type EnvironmentsConfigurationType from ConfigSettings
 */
final class ProcessHelper
{
    private readonly ConfigPaths $paths;
    private readonly ConfigSettings $appConfigSettings;

    /**
     * @psalm-var array<string, BasePackage>
     */
    private readonly array $packages;

    /**
     * @param Composer $composer The composer instance.
     */
    public function __construct(
        private readonly Composer $composer,
    ) {
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $this->appConfigSettings = ConfigSettings::forRootPackage($composer);

        $this->paths = new ConfigPaths(
            $this->appConfigSettings->path(),
            $this->appConfigSettings->options()->sourceDirectory(),
        );
        $this->packages = (new PackagesListBuilder(
            $this->composer,
            $this->appConfigSettings->options()->packageTypes()
        ))->build();
    }

    /**
     * Returns all vendor packages.
     *
     * @psalm-return array<string, BasePackage>
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * Returns vendor packages without packages from the vendor override sublayer.
     *
     * @psalm-return array<string, BasePackage>
     */
    public function getVendorPackages(): array
    {
        $vendorPackages = [];

        foreach ($this->packages as $name => $package) {
            if (!$this->isVendorOverridePackage($name)) {
                $vendorPackages[$name] = $package;
            }
        }

        return $vendorPackages;
    }

    /**
     * Returns vendor packages only from the vendor override sublayer.
     *
     * @psalm-return array<string, BasePackage>
     */
    public function getVendorOverridePackages(): array
    {
        $vendorOverridePackages = [];

        foreach ($this->packages as $name => $package) {
            if ($this->isVendorOverridePackage($name)) {
                $vendorOverridePackages[$name] = $package;
            }
        }

        return $vendorOverridePackages;
    }

    /**
     * Returns the relative path to the package file including the source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param string $filePath The absolute path to the package file.
     *
     * @return string The relative path to the package file including the source directory.
     */
    public function getRelativePackageFilePath(PackageInterface $package, string $filePath): string
    {
        return str_replace("{$this->getPackageRootDirectoryPath($package)}/", '', $filePath);
    }

    /**
     * Returns the relative path to the package file including the package name.
     *
     * @param PackageInterface $package The package instance.
     * @param string $filePath The absolute path to the package file.
     *
     * @return string The relative path to the package file including the package name.
     */
    public function getRelativePackageFilePathWithPackageName(PackageInterface $package, string $filePath): string
    {
        return "{$package->getPrettyName()}/{$this->getRelativePackageFilePath($package, $filePath)}";
    }

    /**
     * Returns the root package configuration.
     *
     * @return array The root package configuration.
     *
     * @psalm-return PackageConfigurationType
     */
    public function getRootPackageConfig(): array
    {
        return $this->appConfigSettings->packageConfiguration();
    }

    /**
     * Returns the environment configuration.
     *
     * @return array The environment configuration.
     *
     * @psalm-return EnvironmentsConfigurationType
     */
    public function getEnvironmentConfig(): array
    {
        return $this->appConfigSettings->environmentsConfiguration();
    }

    /**
     * Returns the config paths instance.
     *
     * @return ConfigPaths The config paths instance.
     */
    public function getPaths(): ConfigPaths
    {
        return $this->paths;
    }

    /**
     * Checks whether to build a merge plan.
     *
     * @return bool Whether to build a merge plan.
     */
    public function shouldBuildMergePlan(): bool
    {
        return $this->appConfigSettings->options()->buildMergePlan();
    }

    /**
     * @return string The merge plan filepath.
     */
    public function getMergePlanFile(): string
    {
        return $this->appConfigSettings->options()->mergePlanFile();
    }

    /**
     * Returns the absolute path to the package root directory.
     *
     * @param PackageInterface $package The package instance.
     *
     * @return string The absolute path to the package root directory.
     */
    private function getPackageRootDirectoryPath(PackageInterface $package): string
    {
        /**
         * @var string Because we use library and composer-plugins only ({@see PackagesListBuilder::getAllPackages()}),
         * which always has installation path.
         */
        return $this->composer
            ->getInstallationManager()
            ->getInstallPath($package);
    }

    /**
     * Checks whether the package is in the vendor override sublayer.
     *
     * @param string $package The package name.
     *
     * @return bool Whether the package is in the vendor override sublayer.
     */
    private function isVendorOverridePackage(string $package): bool
    {
        foreach ($this->appConfigSettings->options()->vendorOverrideLayerPackages() as $pattern) {
            if ($package === $pattern || (new WildcardPattern($pattern))->match($package)) {
                return true;
            }
        }

        return false;
    }
}
