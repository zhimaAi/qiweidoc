<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Yiisoft\Config\ConfigPaths;
use Yiisoft\Config\Options;
use Yiisoft\Strings\WildcardPattern;

use function dirname;
use function is_string;
use function realpath;
use function str_replace;

/**
 * @internal
 */
final class ProcessHelper
{
    private Composer $composer;
    private ConfigPaths $paths;
    private Options $rootPackageOptions;
    private array $rootPackageExtra;

    /**
     * @psalm-var array<string, BasePackage>
     */
    private array $packages;

    /**
     * @param Composer $composer The composer instance.
     */
    public function __construct(Composer $composer)
    {
        /** @psalm-suppress UnresolvableInclude, MixedOperand */
        require_once $composer
                ->getConfig()
                ->get('vendor-dir') . '/autoload.php';
        /** @psalm-suppress MixedArgument */
        $rootPath = realpath(dirname(Factory::getComposerFile()));

        $rootPackageExtra = $composer
            ->getPackage()
            ->getExtra();
        /** @psalm-suppress UnresolvableInclude */
        $this->rootPackageExtra = isset($rootPackageExtra['config-plugin-file'])
            ? (array) (require "$rootPath/{$rootPackageExtra['config-plugin-file']}")
            : $rootPackageExtra
        ;

        $this->composer = $composer;
        $this->rootPackageOptions = new Options($this->rootPackageExtra);
        $this->paths = new ConfigPaths($rootPath, $this->rootPackageOptions->sourceDirectory());
        $this->packages = (new PackagesListBuilder(
            $this->composer,
            $this->rootPackageOptions->packageTypes()
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
     * Returns the absolute path to the package file.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     * @param string $filename The package configuration filename.
     *
     * @return string The absolute path to the package file.
     */
    public function getAbsolutePackageFilePath(PackageInterface $package, Options $options, string $filename): string
    {
        return "{$this->getPackageSourceDirectoryPath($package, $options)}/$filename";
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
     * Returns the package filename excluding the source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     * @param string $filePath The absolute path to the package file.
     *
     * @return string The package filename excluding the source directory.
     */
    public function getPackageFilename(PackageInterface $package, Options $options, string $filePath): string
    {
        return str_replace("{$this->getPackageSourceDirectoryPath($package, $options)}/", '', $filePath);
    }

    /**
     * Returns the package configuration.
     *
     * @param PackageInterface $package The package instance.
     *
     * @return array The package configuration.
     *
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getPackageConfig(PackageInterface $package): array
    {
        return (array) ($package->getExtra()['config-plugin'] ?? []);
    }

    /**
     * Returns the root package configuration.
     *
     * @return array The root package configuration.
     *
     * @psalm-return array<string, string|list<string>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getRootPackageConfig(): array
    {
        return (array) ($this->rootPackageExtra['config-plugin'] ?? []);
    }

    /**
     * Returns the environment configuration.
     *
     * @return array The environment configuration.
     *
     * @psalm-return array<string, array<string, string|string[]>>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getEnvironmentConfig(): array
    {
        return (array) ($this->rootPackageExtra['config-plugin-environments'] ?? []);
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
        return $this->rootPackageOptions->buildMergePlan();
    }

    /**
     * @return string The merge plan filepath.
     */
    public function getMergePlanFile(): string
    {
        return $this->rootPackageOptions->mergePlanFile();
    }

    /**
     * Returns the absolute path to the package source directory {@see Options::sourceDirectory()}.
     *
     * @param PackageInterface $package The package instance.
     * @param Options $options The options instance.
     *
     * @return string The absolute path to the package config directory.
     */
    private function getPackageSourceDirectoryPath(PackageInterface $package, Options $options): string
    {
        $packageConfigDirectory = $options->sourceDirectory() === '' ? '' : "/{$options->sourceDirectory()}";
        return $this->getPackageRootDirectoryPath($package) . $packageConfigDirectory;
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
        foreach ($this->rootPackageOptions->vendorOverrideLayerPackages() as $pattern) {
            if (!is_string($pattern)) {
                continue;
            }

            if ($package === $pattern || (new WildcardPattern($pattern))->match($package)) {
                return true;
            }
        }

        return false;
    }
}
