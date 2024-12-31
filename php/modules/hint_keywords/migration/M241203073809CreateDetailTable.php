<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241203073809CreateDetailTable
 */
final class M241203073809CreateDetailTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<EOF
create table hint_keywords.detail (
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    hint_type  int2 not null default 1,
    from_user_id  varchar(64) not null default '',
    from_role  int2 not null default 1,
    to_role  int2 not null default 1,
    to_user_id  varchar(64) not null default '',
    msg_type  varchar(16) not null default '',
    target_msg_type  varchar(32) not null default 'text',
    msg_id  varchar(191) not null default '',
    msg_time timestamp not null default now(),
    msg_content text not null default '',
    conversation_type int2 not null default 1,
    conversation_id varchar(32) not null default '',
    rule_id int2 not null default 0,
    hint_keyword varchar(191) not null default ''
);

create index on hint_keywords.detail using gin ("msg_content" gin_bigm_ops);
create index on hint_keywords.detail using btree ("rule_id");
create index on hint_keywords.detail using btree ("from_user_id");
create index on hint_keywords.detail using btree ("hint_type");
create index on hint_keywords.detail using btree ("msg_time");
create index on hint_keywords.detail using btree ("target_msg_type");

comment on table hint_keywords.detail is '敏感词触发明细';

comment on column hint_keywords.detail.corp_id is '企业ID';
comment on column hint_keywords.detail.hint_type is '触发类型，1:敏感词，2:敏感行为';
comment on column hint_keywords.detail.from_user_id is '消息发送人ID';
comment on column hint_keywords.detail.from_role is '发送人角色，1:客户，2:员工';
comment on column hint_keywords.detail.to_role is '接收人角色，1:客户，2:员工';
comment on column hint_keywords.detail.to_user_id is '接收人ID，如果 conversation_type 为2 则为群聊ID';
comment on column hint_keywords.detail.msg_type is '消息类型';
comment on column hint_keywords.detail.target_msg_type is '触发的消息类型';
comment on column hint_keywords.detail.msg_id is '消息ID';
comment on column hint_keywords.detail.msg_time is '消息发送时间';
comment on column hint_keywords.detail.msg_content is '消息内容';
comment on column hint_keywords.detail.conversation_type is '会话类型，1:单聊，2:群聊，3:同事会话';
comment on column hint_keywords.detail.conversation_id is '会话id';
comment on column hint_keywords.detail.rule_id is '匹配规则ID';
comment on column hint_keywords.detail.hint_keyword is '匹配到的敏感词内容';

SELECT create_hypertable('hint_keywords.detail', 'msg_time', chunk_time_interval => INTERVAL '1 year');

EOF;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {

        $sql = /** @lang sql */ "drop table hint_keywords.detail";

        migrate_exec($b, $sql);
    }
}
