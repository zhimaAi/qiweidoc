<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118075451CreateCustomersTable
 */
final class M241118075451CreateCustomersTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.customers
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(32) not null default '',
    staff_userid varchar(255) not null default '',
    staff_remark varchar(255) not null default '',
    staff_description varchar(255) not null default '',
    staff_tag_id_list jsonb not null default '[]',
    staff_remark_mobiles jsonb not null default '[]',
    add_way int4 not null default 0,
    oper_userid varchar(255) not null default '',
    external_userid varchar(255) not null default '',
    external_name varchar(255) not null default '',
    avatar varchar(255) not null default '',
    corp_name varchar(255) not null default '',
    corp_full_name varchar(255) not null default '',
    external_type int2 not null default 0,
    gender int2 not null default 0,
    add_time timestamp not null default now(),
    external_profile jsonb not null default '[]',
    add_status int2 not null default 1,
    has_conversation boolean not null default false
);

create index on main.customers using btree ("corp_id");
create index on main.customers using btree ("staff_userid");
create index on main.customers using btree ("external_userid");
create index on main.customers using gin ("staff_tag_id_list");

comment on table main.customers is '客户表';

comment on column main.customers.corp_id is '企业id';
comment on column main.customers.staff_userid is '添加员工userid';
comment on column main.customers.staff_remark is '员工对客户的备注';
comment on column main.customers.staff_description is '员工对客户的描述';
comment on column main.customers.staff_tag_id_list is '员工对客户的标签列表';
comment on column main.customers.staff_remark_mobiles is '员工对客户标记的手机号';
comment on column main.customers.add_way is '添加来源';
comment on column main.customers.oper_userid is '发起添加人userid，员工为userid，客户为external_userid';
comment on column main.customers.external_userid is '客户id';
comment on column main.customers.external_name is '客户昵称';
comment on column main.customers.avatar is '客户头像';
comment on column main.customers.corp_name is '客户所属企业';
comment on column main.customers.corp_full_name is '客户所属企业全称';
comment on column main.customers.external_type is '外部联系人的类型，1表示该外部联系人是微信用户，2表示该外部联系人是企业微信用户';
comment on column main.customers.gender is '性别 0-未知 1-男性 2-女性';
comment on column main.customers.add_time is '添加时间';
comment on column main.customers.external_profile is '外部联系人的自定义展示信息';
comment on column main.customers.add_status is '好友状态 0:已流失，非0 正常';
comment on column main.staff.has_conversation is '是否有过会话记录';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.customers";

        migrate_exec($b, $sql);
    }
}
