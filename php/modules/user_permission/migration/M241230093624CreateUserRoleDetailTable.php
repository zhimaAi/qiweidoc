<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241230093624CreateUserRoleDetailTable
 */
final class M241230093624CreateUserRoleDetailTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL
create table user_permission.user_role_detail
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    corp_id varchar(64) not null default '',

    other_role_id int4 not null default 0,
    staff_userid varchar(64) not null default ''
);

comment on table user_permission.user_role_detail is '员工角色关联';

comment on column user_permission.user_role_detail.other_role_id is '角色ID';
comment on column user_permission.user_role_detail.staff_userid is '员工userid';

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop user_permission.user_role_detail";

        migrate_exec($b, $sql);
    }
}
