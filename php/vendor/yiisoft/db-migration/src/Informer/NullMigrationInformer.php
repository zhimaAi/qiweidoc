<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Informer;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ignores migration process informational messages.
 */
final class NullMigrationInformer implements MigrationInformerInterface
{
    public function beginCreateHistoryTable(string $message): void
    {
        // do nothing
    }

    public function endCreateHistoryTable(string $message): void
    {
        // do nothing
    }

    public function beginCommand(string $message): void
    {
        // do nothing
    }

    public function endCommand(string $message): void
    {
        // do nothing
    }

    public function setIo(?SymfonyStyle $io): void
    {
        // do nothing
    }
}
