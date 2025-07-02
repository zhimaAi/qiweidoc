<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console;

use Spiral\RoadRunner\Console\Command\ArchitectureOption;
use Spiral\RoadRunner\Console\Command\OperatingSystemOption;
use Spiral\RoadRunner\Console\Command\StabilityOption;
use Spiral\RoadRunner\Console\Command\VersionFilterOption;
use Spiral\RoadRunner\Console\Repository\ReleaseInterface;
use Spiral\RoadRunner\Console\Environment\Stability;
use Spiral\RoadRunner\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class VersionsCommand extends Command
{
    private OperatingSystemOption $os;
    private ArchitectureOption $arch;
    private VersionFilterOption $version;
    private StabilityOption $stability;

    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'versions');

        $this->os = new OperatingSystemOption($this);
        $this->arch = new ArchitectureOption($this);
        $this->stability = new StabilityOption($this);

        $this->version = new class($this) extends VersionFilterOption {
            protected function default(): string
            {
                return '*';
            }
        };
    }

    public function getDescription(): string
    {
        return 'Returns a list of all available RoadRunner versions';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->io($input, $output);

        $output->writeln('');
        $output->writeln(' Environment:');
        $output->writeln(\sprintf('   - Version:          <info>%s</info>', $this->version->get($input, $io)));
        $output->writeln(\sprintf('   - Stability:        <info>%s</info>', $this->stability->get($input, $io)));
        $output->writeln('');

        $rows = [];

        $versions = $this->getRepository()
            ->getReleases()
            ->sortByVersion()
            ->minimumStability($this->stability->get($input, $io))
            ->filter(static fn(ReleaseInterface $release): bool => \count($release->getAssets()) > 0)
            ->satisfies($this->version->get($input, $io))
        ;

        foreach ($versions as $version) {
            $rows[] = [
                $this->versionToString($version),
                $this->stabilityToString($version),
                $this->assetsToString($version),
                $this->compatibilityToString($version, $input, $io),
            ];
        }

        $io->table(['Release', 'Stability', 'Binaries', 'Compatibility'], $rows);

        return 0;
    }

    private function compatibilityToString(ReleaseInterface $release, InputInterface $input, StyleInterface $io): string
    {
        $template = '<fg=red> ✖ </> (reason: <comment>%s</comment>)';

        // Validate version
        if (! $release->satisfies(Version::constraint())) {
            return \sprintf($template, 'incompatible version');
        }

        // Validate assets
        $assets = $release->getAssets();

        if ($assets->empty()) {
            return \sprintf($template, 'no binaries');
        }

        // Validate OS
        $assets = $assets->whereOperatingSystem(
            $os = $this->os->get($input, $io),
        );

        if ($assets->empty()) {
            return \sprintf($template, 'no assembly for ' . $os);
        }

        // Validate architecture
        $assets = $assets->whereArchitecture(
            $arch = $this->arch->get($input, $io),
        );

        if ($assets->empty()) {
            return \sprintf($template, 'no assembly for ' . $arch);
        }

        return '<fg=green> ✓ </>';
    }

    private function versionToString(ReleaseInterface $release): string
    {
        return $release->getName();
    }

    private function stabilityToString(ReleaseInterface $release): string
    {
        $stability = $release->getStability();

        switch ($stability) {
            case Stability::STABILITY_STABLE:
                return "<fg=green> $stability </>";

            case Stability::STABILITY_RC:
                return "<fg=blue> $stability </>";

            case Stability::STABILITY_BETA:
                return "<fg=yellow> $stability </>";

            case Stability::STABILITY_ALPHA:
                return "<fg=red> $stability </>";

            default:
                return "<bg=red;bg=white> $stability </>";
        }
    }

    private function assetsToString(ReleaseInterface $release): string
    {
        $count = $release->getAssets()
            ->count()
        ;

        if ($count > 0) {
            return \sprintf('<fg=green> ✓ </> (<comment>%d</comment>)', $count);
        }

        return '<fg=red> ✖ </>';
    }
}
