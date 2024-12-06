<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118074915CreateStaffTagTable
 */
final class M241118074915CreateStaffTagTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.staff_tag
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    tag_id int8 not null default 0,
    tag_name varchar(255) not null default '',
    corp_id varchar(255) not null default ''
);

create index on main.staff_tag using btree ("corp_id", "tag_id");

comment on table main.staff_tag is '员工的标签表';
comment on column main.staff_tag.tag_id is '企微的标签id';
comment on column main.staff_tag.tag_name is '企微的标签名称';
comment on column main.staff_tag.corp_id is '企业id';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.staff_tag";

        migrate_exec($b, $sql);
    }
}
