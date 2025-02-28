<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M250113080109CreateSettingsTable
 */
final class M250113080109CreateSettingsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table archive_staff.settings
(
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    key varchar(64) primary key,
    value varchar(512) not null default ''
);
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table archive_staff.settings";
        migrate_exec($b, $sql);
    }
}
