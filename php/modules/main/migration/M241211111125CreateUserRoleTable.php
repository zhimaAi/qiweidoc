<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241211111125CreateUserRoleTable
 */
final class M241211111125CreateUserRoleTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.user_role
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    role_name varchar(32) not null default '普通员工',
    permission_config jsonb not null default '[]'
);

comment on table main.user_role is '用户角色表';
comment on column main.user_role.role_name is '角色名';
comment on column main.user_role.permission_config is '权限配置';

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.user_role";

        migrate_exec($b, $sql);
    }
}
