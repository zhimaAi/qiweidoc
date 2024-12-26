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
create table timeout_reply_group.rules
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(64) not null default '',
    name varchar(64) not null default '',

    group_type int2 not null default 1,
    group_chat_id_list jsonb not null default '[]',
    group_staff_userid_list jsonb not null default '[]',
    group_keyword_list jsonb not null default '{"include_list": [], "exclude_list": []}',

    inspect_time_type int2 not null default 1,
    custom_time_list jsonb not null default '[]',
    remind_rules jsonb not null default '[]',

    is_remind_group_owner bool not null default true,
    enabled bool not null default false
);

create index on timeout_reply_group.rules using btree ("corp_id");

comment on table timeout_reply_group.rules is '规则表';

comment on column timeout_reply_group.rules.corp_id is '企业id';
comment on column timeout_reply_group.rules.name is '名称';
comment on column timeout_reply_group.rules.group_staff_userid_list is '添加群聊类型 1指定群聊 2群成员 3群关键词';
comment on column timeout_reply_group.rules.group_chat_id_list is '指定群聊id列表';
comment on column timeout_reply_group.rules.group_staff_userid_list is '包含群成员的列表';
comment on column timeout_reply_group.rules.group_keyword_list is '包含群关键字列表';

comment on column timeout_reply_group.rules.inspect_time_type is '质检时间类型 1全天质检 2工作时间 3自定义时间';
comment on column timeout_reply_group.rules.custom_time_list is '自定义时间段';
comment on column timeout_reply_group.rules.remind_rules is '提醒规则';
comment on column timeout_reply_group.rules.is_remind_group_owner is '是否提醒群主';
comment on column timeout_reply_group.rules.enabled is '是否已启用';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table timeout_reply_group.rules";

        migrate_exec($b, $sql);
    }
}
