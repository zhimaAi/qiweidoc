<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241231015352CreateSettingsTable
 */
final class M241231015352CreateSettingsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table custom_brand.settings
(
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    key varchar(64) primary key,
    value varchar(512) not null default ''
);

comment on table custom_brand.settings is '配置';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table custom_brand.settings";
        migrate_exec($b, $sql);
    }
}
