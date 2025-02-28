<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241231103130CreateRolePermissionDetailTable
 */
final class M241231103130CreateRolePermissionDetailTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL
create table user_permission.role_permission_detail
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    corp_id varchar(64) not null default '',

    permission_index int4 not null default 0,
    server_name varchar(255) not null default '',
    description varchar(255) not null default '',
    permission_node_title varchar(255) not null default '',
    permission_key varchar(255) not null default '',
    permission_detail jsonb not null default '[]'
);

comment on table user_permission.role_permission_detail is '权限明细';

comment on column user_permission.role_permission_detail.permission_index is '排序';
comment on column user_permission.role_permission_detail.server_name is '权限服务名';
comment on column user_permission.role_permission_detail.description is '描述';
comment on column user_permission.role_permission_detail.permission_node_title is '细分权限名';
comment on column user_permission.role_permission_detail.permission_detail is '权限组';
comment on column user_permission.role_permission_detail.permission_key is '权限唯一标识';

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop user_permission.role_permission_detail";

        migrate_exec($b, $sql);
    }
}
