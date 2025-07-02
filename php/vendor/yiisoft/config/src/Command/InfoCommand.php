<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Package\BasePackage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Config\Composer\ConfigSettings;
use Yiisoft\Config\Composer\Options;

final class InfoCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('yii-config-info')
            ->addArgument('package', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $composer = $this->requireComposer();

        $packageName = $input->getArgument('package');
        if (is_string($packageName)) {
            $package = $composer->getRepositoryManager()->getLocalRepository()->findPackage($packageName, '*');
            if ($package === null) {
                $io->error('Package "' . $packageName . '" not found.');
                return 1;
            }
            return $this->vendorPackage($composer, $package, $io);
        }

        return $this->rootPackage($composer, $io);
    }

    private function vendorPackage(Composer $composer, BasePackage $package, SymfonyStyle $io): int
    {
        $settings = ConfigSettings::forVendorPackage($composer, $package);
        if (empty($settings->packageConfiguration())) {
            $io->writeln('');
            $io->writeln('<fg=gray>Configuration don\'t found in package "' . $package->getName() . '".</>');
            return 0;
        }

        $io->title('Yii Config — Package "' . $package->getName() . '"');

        $io->writeln('Source directory: ' . $settings->path() . '/' . $settings->options()->sourceDirectory());

        $io->section('Configuration groups');
        $this->writeConfiguration($io, $settings->packageConfiguration());

        return 0;
    }

    private function rootPackage(Composer $composer, SymfonyStyle $io): int
    {
        $settings = ConfigSettings::forRootPackage($composer);
        $options = $settings->options();
        $sourceDirectory = $settings->options()->sourceDirectory();
        $mergePlanFilePath = $settings->path() . '/'
            . (empty($sourceDirectory) ? '' : ($sourceDirectory . '/'))
            . $options->mergePlanFile();

        $io->title('Yii Config — Root Configuration');

        $io->section('Options');
        $io->table([], [
            [
                'Build merge plan',
                $options->buildMergePlan() ? '<fg=green>yes</>' : '<fg=red>no</>',
            ],
            [
                'Merge plan file path',
                file_exists($mergePlanFilePath)
                    ? '<fg=green>' . $mergePlanFilePath . '</>'
                    : '<fg=red>' . $mergePlanFilePath . ' (not exists)</>',
            ],
            [
                'Package types',
                empty($options->packageTypes()) ? '<fg=red>not set</>' : implode(', ', $options->packageTypes()),
            ],
            [
                'Source directory',
                $settings->path() . '/' . $options->sourceDirectory(),
            ],
            [
                'Vendor override layer packages',
                empty($options->vendorOverrideLayerPackages())
                    ? '<fg=gray>not set</>'
                    : implode(', ', $options->vendorOverrideLayerPackages()),
            ],
        ]);

        $io->section('Configuration groups');
        $this->writeConfiguration($io, $settings->packageConfiguration());

        $io->section('Environments');
        $environmentsConfiguration = $settings->environmentsConfiguration();
        if (empty($environmentsConfiguration)) {
            $io->writeln('<fg=gray>not set</>');
        } else {
            $isFirst = true;
            foreach ($environmentsConfiguration as $environment => $groups) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $io->newLine();
                }
                $io->write(' <fg=bright-magenta>' . $environment . '</>');
                if (empty($groups)) {
                    $io->writeln(' <fg=gray>(empty)</>');
                } else {
                    $io->newLine();
                    $this->writeConfiguration($io, $groups, offset: 2, addSeparateLine: false);
                }
            }
        }

        return 0;
    }

    /**
     * @psalm-param array<string, string|string[]> $configuration
     */
    private function writeConfiguration(
        SymfonyStyle $io,
        array $configuration,
        int $offset = 1,
        bool $addSeparateLine = true,
    ): void {
        foreach ($configuration as $group => $values) {
            $this->writeGroup($io, $group, $values, $offset);
            if ($addSeparateLine) {
                $io->newLine();
            }
        }
    }

    /**
     * @param string|string[] $items
     */
    private function writeGroup(SymfonyStyle $io, string $group, array|string $items, int $offset): void
    {
        $prefix = str_repeat(' ', $offset);
        $items = (array) $items;
        $io->write($prefix . '<fg=cyan>' . $group . '</>');
        if (empty($items)) {
            $io->write(' <fg=gray>(empty)</>');
        } else {
            foreach ($items as $item) {
                $io->newLine();
                $io->write($prefix . ' - ' . (Options::isVariable($item) ? '<fg=green>' . $item . '</>' : $item));
            }
        }
        $io->newLine();
    }
}
