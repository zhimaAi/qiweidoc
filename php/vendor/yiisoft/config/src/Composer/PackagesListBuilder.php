<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;

use function array_key_exists;
use function in_array;

/**
 * @internal
 */
final class PackagesListBuilder
{
    public function __construct(
        private readonly Composer $composer,
        private readonly array $packageTypes,
    ) {
    }

    /**
     * Builds and returns packages.
     *
     * @return array<string, BasePackage>
     */
    public function build(): array
    {
        $allPackages = $this->getAllPackages();

        $packageDepths = [];
        $this->calculatePackageDepths($allPackages, $packageDepths, 0, $this->composer->getPackage(), true);

        $result = [];
        foreach ($this->getSortedPackageNames($packageDepths) as $name) {
            if (array_key_exists($name, $allPackages)) {
                $result[$name] = $allPackages[$name];
            }
        }

        return $result;
    }

    /**
     * Get package names stable sorted by depth
     *
     * @param array<string, int> $packageDepths
     *
     * @return string[]
     */
    private function getSortedPackageNames(array $packageDepths): array
    {
        $n = 0;
        foreach ($packageDepths as $name => $depth) {
            $packageDepths[$name] = [$depth, ++$n];
        }

        /** @psalm-var array<string, array{0:int,1:int}> $packageDepths */

        uasort($packageDepths, static function (array $a, array $b) {
            $result = $a[0] <=> $b[0];
            return $result === 0 ? $a[1] <=> $b[1] : $result;
        });

        return array_keys($packageDepths);
    }

    /**
     * @param array<string, BasePackage> $allPackages
     * @param array<string, int> $packageDepths
     */
    private function calculatePackageDepths(
        array $allPackages,
        array &$packageDepths,
        int $depth,
        PackageInterface $package,
        bool $includingDev
    ): void {
        $name = $package->getPrettyName();

        $packageProcessed = array_key_exists($name, $packageDepths);

        if (!$packageProcessed || $packageDepths[$name] < $depth) {
            $packageDepths[$name] = $depth;
        }

        // Prevent infinite loop in case of circular dependencies
        if ($packageProcessed) {
            return;
        }

        ++$depth;

        $dependencies = $includingDev
            ? [...array_keys($package->getRequires()), ...array_keys($package->getDevRequires())]
            : array_keys($package->getRequires());

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency, $allPackages)) {
                $this->calculatePackageDepths($allPackages, $packageDepths, $depth, $allPackages[$dependency], false);
            }
        }
    }

    /**
     * @return array<string, BasePackage>
     */
    private function getAllPackages(): array
    {
        $packages = $this->composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();

        $result = [];
        foreach ($packages as $package) {
            if (!in_array($package->getType(), $this->packageTypes)) {
                continue;
            }

            $result[$package->getPrettyName()] = $package;
        }

        return $result;
    }
}
