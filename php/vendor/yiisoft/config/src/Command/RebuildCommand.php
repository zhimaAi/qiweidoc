<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Config\Composer\MergePlanProcess;

/**
 * `RebuildCommand` crawls all the configuration files and updates the merge plan file.
 */
final class RebuildCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('yii-config-rebuild')
            ->setDescription('Crawls all the configuration files and updates the merge plan file.')
            ->setHelp('This command crawls all the configuration files and updates the merge plan file.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new MergePlanProcess($this->requireComposer());
        return 0;
    }
}
