<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241230093510CreateOtherRoleTable
 */
final class M241230093510CreateOtherRoleTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL
create table user_permission.other_role
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    corp_id varchar(64) not null default '',

    role_name varchar(32) not null default '普通员工',
    permission_config jsonb not null default '[]'
);

comment on table user_permission.other_role is '用户角色表';

comment on column user_permission.other_role.role_name is '角色名';
comment on column user_permission.other_role.permission_config is '权限配置';

alter sequence user_permission.other_role_id_seq restart with 100;

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop user_permission.other_role";

        migrate_exec($b, $sql);
    }
}
