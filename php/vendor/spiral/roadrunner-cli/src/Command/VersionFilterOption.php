<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Spiral\RoadRunner\Console\Repository\ReleaseInterface;
use Spiral\RoadRunner\Console\Repository\ReleasesCollection;
use Spiral\RoadRunner\Console\Repository\RepositoryInterface;
use Spiral\RoadRunner\Version as RoadRunnerVersion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class VersionFilterOption extends Option
{
    public function __construct(Command $command, string $name = 'filter', string $short = 'f')
    {
        parent::__construct($command, $name, $short);
    }

    public function choices(ReleasesCollection $releases): string
    {
        $versions = $releases
            ->map(static fn(ReleaseInterface $release): string => $release->getVersion())
            ->toArray()
        ;

        return \implode(', ', \array_unique($versions));
    }

    public function find(InputInterface $input, StyleInterface $io, RepositoryInterface $repo): ReleasesCollection
    {
        $constraint = $this->get($input, $io);

        // All available releases
        $available = $repo->getReleases()
            ->sortByVersion()
        ;

        // With constraints
        $filtered = $available->satisfies($constraint);

        $this->validateNotEmpty($filtered, $available, $constraint);

        return $filtered;
    }

    protected function getDescription(): string
    {
        return 'Required version of RoadRunner binaries';
    }

    protected function default(): string
    {
        return RoadRunnerVersion::constraint();
    }

    private function validateNotEmpty(ReleasesCollection $filtered, ReleasesCollection $all, string $constraint): void
    {
        if ($filtered->empty()) {
            $header = 'Could not find any available RoadRunner binary version which meets version criterion (--%s=%s)';
            $header = \sprintf($header, $this->name, $constraint);

            $footer = 'Available: ' . $this->choices($all);

            throw new \UnexpectedValueException(\implode("\n", [$header, $footer]));
        }
    }
}
