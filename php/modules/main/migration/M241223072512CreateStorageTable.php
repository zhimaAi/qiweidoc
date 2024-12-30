<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241223072512CreateStorageTable
 */
final class M241223072512CreateStorageTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table main.storage
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    hash char(32) not null,
    original_filename varchar(255) not null default '',
    file_extension varchar(16) not null default '',
    mime_type varchar(128) not null default '',
    file_size int8 not null default 0,
    is_deleted_local bool default false,

    local_storage_bucket varchar(64) not null default '',
    local_storage_object_key varchar(1024) not null default '',
    local_storage_expired_at timestamp default null,

    cloud_storage_setting_id int8 not null default 0,
    cloud_storage_object_key varchar(1024) not null default ''
);

create index on main.storage using hash (hash);
create index on main.storage using gin (original_filename gin_bigm_ops);
create index on main.storage using btree (local_storage_expired_at);
create index on main.storage using btree (is_deleted_local);
create index on main.storage using btree (cloud_storage_setting_id, cloud_storage_object_key);
create index on main.storage using btree (created_at);

comment on table main.storage is '存储表';

comment on column main.storage.hash is '文件的md5 hash值';
comment on column main.storage.original_filename is '原始文件名';
comment on column main.storage.file_extension is '文件扩展名';
comment on column main.storage.mime_type is '文件MIME类型';
comment on column main.storage.file_size is '文件大小,单位是字节';
comment on column main.storage.is_deleted_local is '是否已删除本地文件';

comment on column main.storage.local_storage_bucket is '本地存储的桶名称';
comment on column main.storage.local_storage_object_key is '本地存储的对象key';
comment on column main.storage.local_storage_expired_at is '本地存储的文件到期时间,到期自动删除';

comment on column main.storage.cloud_storage_setting_id is '云对象存储的配置id';
comment on column main.storage.cloud_storage_object_key is '云对象存储的对象key';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table main.storage";

        migrate_exec($b, $sql);
    }
}
