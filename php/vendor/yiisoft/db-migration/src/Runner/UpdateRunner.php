<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Runner;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Migration\Migrator;

use function microtime;
use function sprintf;

final class UpdateRunner
{
    private ?SymfonyStyle $io = null;

    public function __construct(private Migrator $migrator)
    {
    }

    public function setIo(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    public function run(MigrationInterface $migration, int|null $number = null): void
    {
        if ($this->io === null) {
            throw new RuntimeException('You need to set output decorator via `setIo()`.');
        }

        $num = $number !== null ? $number . '. ' : '';
        $className = $migration::class;

        $this->io->title("\n{$num}Applying $className");

        $start = microtime(true);

        try {
            $this->migrator->up($migration);
        } catch (Throwable $e) {
            $time = microtime(true) - $start;

            $this->io->writeln(
                "\n\n\t<error>>>> [ERROR] - Not applied (time: " . sprintf('%.3f', $time) . 's)</error>'
            );

            throw $e;
        }

        $time = microtime(true) - $start;

        $this->io->writeln(
            "\n\t<info>>>> [OK] - Applied (time: " . sprintf('%.3f', $time) . 's)</info>'
        );
    }
}
