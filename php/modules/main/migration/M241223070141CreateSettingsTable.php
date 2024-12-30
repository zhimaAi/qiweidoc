<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241223070141CreateSettingsTable
 */
final class M241223070141CreateSettingsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table main.settings
(
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    key varchar(64) primary key,
    value text not null default ''
);

comment on table main.settings is '配置表';

comment on column main.settings.key is '键';
comment on column main.settings.value is '值';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table main.settings";

        migrate_exec($b, $sql);
    }
}
