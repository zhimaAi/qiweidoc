<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118082709CreateChatConversationsTable
 */
final class M241118082709CreateChatConversationsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.chat_conversations
(
    id varchar(32) primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(32) not null default '',
    "from" varchar(64) not null default '',
    "to" varchar(64) not null default '',
    type int2 not null default 1,
    from_role int2 not null default 1,
    to_role int2 not null default 1,
    last_msg_time timestamp not null default now()
);

create index on main.chat_conversations using btree ("type", "from", "to");
create index on main.chat_conversations using btree ("updated_at");

comment on table main.chat_conversations is '企微会话存档的会话表';

comment on column main.chat_conversations.corp_id is '企业id';
comment on column main.chat_conversations.from is '消息发出方,员工或客户';
comment on column main.chat_conversations.to is '消息接收方,员工、客户或群聊id';
comment on column main.chat_conversations.type is '会话类别 1单聊 2群聊 3同事聊天';
comment on column main.chat_conversations.from_role is '消息发出方角色类型 1客户 2员工 3群';
comment on column main.chat_conversations.to_role is '消息接收方角色类型 1客户 2员工 3群';
comment on column main.chat_conversations.last_msg_time is '上一次会话时间';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.chat_conversations";

        migrate_exec($b, $sql);
    }
}
