<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Repository;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Console\Environment\Stability;

abstract class Release implements ReleaseInterface
{
    private string $name;

    #[ExpectedValues(valuesFromClass: Stability::class)]
    private string $stability;

    private string $version;
    private AssetsCollection $assets;
    private string $repository;

    public function __construct(string $name, string $version, string $repository, iterable $assets = [])
    {
        $this->version = $version;
        $this->repository = $repository;

        $this->name = $this->simplifyReleaseName($name);
        $this->assets = AssetsCollection::create($assets);

        $this->stability = $this->parseStability($version);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getRepositoryName(): string
    {
        return $this->repository;
    }

    #[ExpectedValues(valuesFromClass: Stability::class)]
    public function getStability(): string
    {
        return $this->stability;
    }

    public function getAssets(): AssetsCollection
    {
        return $this->assets;
    }

    public function satisfies(string $constraint): bool
    {
        return Semver::satisfies($this->getName(), $constraint);
    }

    private function parseStability(string $version): string
    {
        return VersionParser::parseStability($version);
    }

    private function simplifyReleaseName(string $name): string
    {
        $version = (new VersionParser())->normalize($name);

        $parts = \explode('-', $version);
        $number = \substr($parts[0], 0, -2);

        return isset($parts[1])
            ? $number . '-' . $parts[1]
            : $number
        ;
    }
}
