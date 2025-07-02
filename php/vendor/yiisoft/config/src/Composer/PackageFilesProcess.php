<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Yiisoft\Config\ConfigPaths;

use function glob;
use function in_array;
use function is_file;
use function substr;

/**
 * @internal
 */
final class PackageFilesProcess
{
    private readonly ProcessHelper $helper;

    /**
     * @var PackageFile[]
     */
    private array $packageFiles = [];

    /**
     * @param Composer $composer The composer instance.
     * @param string[] $packageNames The pretty package names to build.
     * If the array is empty, the files of all packages will be build.
     */
    public function __construct(
        private readonly Composer $composer,
        array $packageNames = [],
    ) {
        $this->helper = new ProcessHelper($composer);
        $this->process($packageNames);
    }

    /**
     * Returns the processed package configuration files.
     *
     * @return PackageFile[] The processed package configuration files.
     */
    public function files(): array
    {
        return $this->packageFiles;
    }

    /**
     * Returns the config paths instance.
     *
     * @return ConfigPaths The config paths instance.
     */
    public function paths(): ConfigPaths
    {
        return $this->helper->getPaths();
    }

    /**
     * @param string[] $packageNames The pretty package names to build.
     * If the array is empty, the files of all packages will be build.
     */
    private function process(array $packageNames): void
    {
        foreach ($this->helper->getPackages() as $package) {
            $configSettings = ConfigSettings::forVendorPackage($this->composer, $package);
            foreach ($configSettings->packageConfiguration() as $files) {
                $files = (array) $files;

                foreach ($files as $file) {
                    $isOptional = false;

                    if (Options::isOptional($file)) {
                        $isOptional = true;
                        $file = substr($file, 1);
                    }

                    if (
                        Options::isVariable($file) ||
                        (!empty($packageNames) && !in_array($package->getPrettyName(), $packageNames, true))
                    ) {
                        continue;
                    }

                    $absoluteFilePath = $configSettings->configPath() . '/' . $file;

                    if (Options::containsWildcard($file)) {
                        $matches = glob($absoluteFilePath);

                        if (empty($matches)) {
                            continue;
                        }

                        foreach ($matches as $match) {
                            $this->packageFiles[] = new PackageFile($configSettings, $match);
                        }

                        continue;
                    }

                    if ($isOptional && !is_file($absoluteFilePath)) {
                        continue;
                    }

                    $this->packageFiles[] = new PackageFile($configSettings, $absoluteFilePath);
                }
            }
        }
    }
}
