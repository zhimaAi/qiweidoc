<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241228050717CreateCloudStorageSettingTable
 */
final class M241228050717CreateCloudStorageSettingTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table main.cloud_storage_setting
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    provider varchar(32) not null default '',
    region varchar(32) not null default '',
    endpoint varchar(64) not null default '',
    bucket varchar(32) not null default '',
    access_key varchar(64) not null default '',
    secret_key varchar(64) not null default ''
);

comment on column main.cloud_storage_setting.provider is '提供商';
comment on column main.cloud_storage_setting.region is '地区';
comment on column main.cloud_storage_setting.endpoint is '端点';
comment on column main.cloud_storage_setting.bucket is '桶';
comment on column main.cloud_storage_setting.access_key is 'access_key';
comment on column main.cloud_storage_setting.secret_key is 'secret_key';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table main.cloud_storage_setting";
        migrate_exec($b, $sql);
    }
}
