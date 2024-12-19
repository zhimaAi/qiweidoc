<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241211074935CreateRulesTable
 */
final class M241211074935CreateRulesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table timeout_reply_single.rules
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(64) not null default '',
    name varchar(64) not null default '',
    staff_userid_list jsonb not null default '[]',
    inspect_time_type int2 not null default 1,
    custom_time_list jsonb not null default '[]',
    timeout_unit int2 not null default 1,
    timeout_value int4 not null default 1,
    is_remind_staff_designation bool not null default false,
    is_remind_staff_himself bool not null default false,
    designate_remind_userid_list jsonb not null default '[]',
    enabled bool not null default false
);

create index on timeout_reply_single.rules using btree ("corp_id");

comment on table timeout_reply_single.rules is '规则表';

comment on column timeout_reply_single.rules.corp_id is '企业id';
comment on column timeout_reply_single.rules.name is '名称';
comment on column timeout_reply_single.rules.staff_userid_list is '质检员工列表';
comment on column timeout_reply_single.rules.inspect_time_type is '质检时间类型 1全天质检 2工作时间 3自定义时间';
comment on column timeout_reply_single.rules.custom_time_list is '自定义时间段';
comment on column timeout_reply_single.rules.timeout_unit is '超时单位 1分钟 2小时 3天';
comment on column timeout_reply_single.rules.timeout_value is '超时时间';
comment on column timeout_reply_single.rules.is_remind_staff_designation is '是否提醒指定员工';
comment on column timeout_reply_single.rules.is_remind_staff_himself is '是否提醒员工本人';
comment on column timeout_reply_single.rules.designate_remind_userid_list is '提醒指定员工列表';
comment on column timeout_reply_single.rules.enabled is '是否已启用';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table timeout_reply_single.rules";

        migrate_exec($b, $sql);
    }
}
