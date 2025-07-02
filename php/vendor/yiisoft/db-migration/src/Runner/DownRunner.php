<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Runner;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

use function microtime;
use function sprintf;

final class DownRunner
{
    private ?SymfonyStyle $io = null;

    public function __construct(private Migrator $migrator)
    {
    }

    public function setIo(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function run(RevertibleMigrationInterface $migration, int|null $number = null): void
    {
        if ($this->io === null) {
            throw new RuntimeException('You need to set output decorator via `setIo()`.');
        }

        $num = $number !== null ? $number . '. ' : '';
        $className = $migration::class;

        $this->io->title("\n{$num}Reverting $className");

        $start = microtime(true);

        try {
            $this->migrator->down($migration);
        } catch (Throwable $e) {
            $time = microtime(true) - $start;

            $this->io->writeln(
                "\n\n\t<error>>>> [ERROR] - Not reverted (time: " . sprintf('%.3f', $time) . 's)</error>'
            );

            throw $e;
        }

        $time = microtime(true) - $start;

        $this->io->writeln(
            "\n\t<info>>>> [OK] - Reverted (time: " . sprintf('%.3f', $time) . 's)</info>'
        );
    }
}
