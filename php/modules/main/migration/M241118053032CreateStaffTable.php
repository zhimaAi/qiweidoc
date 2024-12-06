<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118053032CreateStaffTable
 */
final class M241118053032CreateStaffTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.staff
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    name varchar(255) not null default '',
    corp_id varchar(32) not null default '',
    department jsonb not null default '[]',
    position varchar(255) not null default '',
    status int4 not null default 0,
    isleader int2 not null default 0,
    extattr jsonb not null default '[]',
    "order" jsonb not null default '[]',
    enable int2 not null default 0,
    main_department int4 not null default 0,
    alias varchar(255) not null default '',
    is_leader_in_dept jsonb not null default '[]',
    userid varchar(255) not null default '',
    direct_leader jsonb not null default '[]',
    tag_ids jsonb not null default '[]',
    cst_total int4 not null default 0,
    chat_status int2 not null default 0,
    has_conversation boolean not null default false
);

create index on main.staff using btree ("corp_id", "userid");
create index on main.staff using btree ("corp_id", "tag_ids");

comment on table main.staff is '员工表';

comment on column main.staff.name is '员工姓名';
comment on column main.staff.corp_id is '员工所属企业';
comment on column main.staff.department is '员工所属部门id列表';
comment on column main.staff.position is '职务信息';
comment on column main.staff.status is '激活状态 1=已激活，2=已禁用，4=未激活，5=退出企业';
comment on column main.staff.isleader is '是否为部门负责人';
comment on column main.staff.extattr is '扩展属性';
comment on column main.staff.order is '部门内的排序值，默认为0。数量必须和department一致，数值越大排序越前面';
comment on column main.staff.main_department is '成员所属部门id列表';
comment on column main.staff.alias is '别名';
comment on column main.staff.is_leader_in_dept is '在所在的部门内是否为部门负责人';
comment on column main.staff.userid is '成员UserID。对应管理端的账号';
comment on column main.staff.direct_leader is '直属上级UserID，返回在应用可见范围内的直属上级列';
comment on column main.staff.tag_ids is '标签id列表';
comment on column main.staff.cst_total is '客户总数';
comment on column main.staff.chat_status is '会话存档状态 0未开启过,，1:开启中，2:历史开启过';
comment on column main.staff.has_conversation is '是否有过会话记录';
SQL;


        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.staff;";

        migrate_exec($b, $sql);
    }
}
