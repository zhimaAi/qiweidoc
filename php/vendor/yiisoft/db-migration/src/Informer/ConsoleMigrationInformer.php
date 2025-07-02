<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Informer;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes migration process informational messages into console.
 */
final class ConsoleMigrationInformer implements MigrationInformerInterface
{
    private ?SymfonyStyle $io = null;

    public function beginCreateHistoryTable(string $message): void
    {
        $this->io?->section($message);
    }

    public function endCreateHistoryTable(string $message): void
    {
        $this->io?->writeln("\t<fg=green>>>> [OK] - '.$message.'.</>");
    }

    public function beginCommand(string $message): void
    {
        $this->io?->write('    > ' . $message . ' ...');
    }

    public function endCommand(string $message): void
    {
        $this->io?->writeln(' ' . $message);
    }

    public function setIo(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }
}
