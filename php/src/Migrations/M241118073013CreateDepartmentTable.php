<?php

declare(strict_types=1);

namespace App\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118073013CreateDepartmentTable
 */
final class M241118073013CreateDepartmentTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table department
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    department_id int8 not null default 0,
    corp_id varchar(32) not null default '',
    "order" int4 not null default 0,
    parent_id int4 not null default 0,
    name text not null default '',
    department_leader jsonb not null default '[]'
);

create unique index on department using btree ("corp_id", "department_id");

comment on table department is '企微部门';

comment on column department.department_id is '企微的部门id';
comment on column department.corp_id is '企业id';
comment on column department.order is '部门排序';
comment on column department.parent_id is '上级部门id';
comment on column department.name is '部门名称';
comment on column department.department_leader is '部门负责人的UserID';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table department";

        migrate_exec($b, $sql);
    }
}
