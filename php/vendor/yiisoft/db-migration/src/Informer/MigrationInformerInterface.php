<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Informer;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Handles migration process informational messages.
 */
interface MigrationInformerInterface
{
    public function beginCreateHistoryTable(string $message): void;

    public function endCreateHistoryTable(string $message): void;

    public function beginCommand(string $message): void;

    public function endCommand(string $message): void;

    public function setIo(?SymfonyStyle $io): void;
}
