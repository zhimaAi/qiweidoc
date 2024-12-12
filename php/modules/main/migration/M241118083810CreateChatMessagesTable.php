<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118083810CreateChatMessagesTable
 */
final class M241118083810CreateChatMessagesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.chat_messages
(
    msg_id varchar(64) not null,
    created_at timestamp(0) not null default now(),
    updated_at timestamp(0) not null default now(),

    corp_id varchar(32) not null default '',
    seq int8 not null default 0,
    public_key_ver int4 not null default 0,
    action varchar(32) not null default '',
    "from" varchar(64) not null default '',
    to_list jsonb not null default '[]',
    roomid varchar(64) not null default '',
    msg_time timestamp not null default now(),
    msg_type varchar(32) not null default '',
    raw_content jsonb not null default '[]',
    conversation_id varchar(32) not null default 0,
    from_role int2 not null default 1,
    to_role int2 not null default 1,
    msg_content text not null default '',
    conversation_type int2 not null default 1
);

-- 创建索引
create index on main.chat_messages using btree (msg_id);
create index on main.chat_messages using btree ("corp_id", "conversation_id", "seq");
create index on main.chat_messages using btree ("from");
create index on main.chat_messages using gin ("to_list");
create index on main.chat_messages using btree ("roomid");
create index on main.chat_messages using gin ("msg_content" gin_bigm_ops);

comment on table main.chat_messages is '企微会话存档的聊天消息表';

comment on column main.chat_messages.corp_id is '企业id';
comment on column main.chat_messages.seq is '消息序号';
comment on column main.chat_messages.public_key_ver is '公钥版本';
comment on column main.chat_messages.action is '消息动作,撤回消息是为recall，其他为send。String类型';
comment on column main.chat_messages.from is '消息发送方,userid';
comment on column main.chat_messages.to_list is '消息接收方列表';
comment on column main.chat_messages.roomid is '群聊id';
comment on column main.chat_messages.msg_time is '消息发出时间';
comment on column main.chat_messages.msg_type is '消息类型';
comment on column main.chat_messages.raw_content is '企微原始消息内容';
comment on column main.chat_messages.conversation_id is '关联的会话id';
comment on column main.chat_messages.from_role is '发送者身份';
comment on column main.chat_messages.to_role is '接收者身份';
comment on column main.chat_messages.msg_content is '从解密后的消息中提取的消息内容，用于展示和检索';
comment on column main.chat_messages.conversation_type is '会话类别 1单聊 2群聊 3同事聊天';

SELECT create_hypertable('main.chat_messages', 'msg_time', chunk_time_interval => INTERVAL '1 month');
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
DROP TABLE IF EXISTS main.chat_messages CASCADE;
SQL;

        migrate_exec($b, $sql);
    }
}
