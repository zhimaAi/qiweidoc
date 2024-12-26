<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241211090320CreateTimeoutMessagesTable
 */
final class M241211090320CreateTimeoutMessagesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table timeout_reply_group.timeout_messages
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(64) not null default 0,
    rule_id int8 not null default 0,
    remind_rule_index int2 not null default 0,
    msg_id varchar(64) not null default ''
);

create index on timeout_reply_group.timeout_messages using btree (corp_id, msg_id);

comment on table timeout_reply_group.timeout_messages is '超时未回复缓存表';
comment on column timeout_reply_group.timeout_messages.corp_id is '企业id';
comment on column timeout_reply_group.timeout_messages.rule_id is '关联的规则id';
comment on column timeout_reply_group.timeout_messages.remind_rule_index is '关联的提醒规则序号';
comment on column timeout_reply_group.timeout_messages.msg_id is '消息id';

SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table timeout_reply_group.timeout_messages;";

        migrate_exec($b, $sql);
    }
}
