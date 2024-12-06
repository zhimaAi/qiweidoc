<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118050451CreateCorpsTable
 */
final class M241118050451CreateCorpsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<< SQL
create table main.corps
(
    id varchar(32) not null primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    agent_id int4 not null default 0,
    agent_secret varchar(255) not null default '',
    chat_public_key text not null default '',
    chat_private_key text not null default '',
    chat_secret varchar(255) not null default '',
    chat_public_key_version int4 not null default 0,
    chat_pull_status int2 not null default 0,
    chat_seq int8 not null default 0,
    chat_last_err_msg varchar(255) not null default '',
    sync_staff_time timestamp not null default now(),
    sync_group_time timestamp not null default now(),
    sync_customer_time timestamp not null default now()
);

comment on table main.corps is '企业信息表';

comment on column main.corps.agent_id is '应用id';
comment on column main.corps.agent_secret is '应用密钥';
comment on column main.corps.chat_public_key is '会话存档公钥';
comment on column main.corps.chat_private_key is '会话存档私钥';
comment on column main.corps.chat_secret is '会话存档密钥';
comment on column main.corps.chat_public_key_version is '会话存档公钥版本';
comment on column main.corps.chat_pull_status is '会话存档拉取状态 0未拉取 1正在拉取 2拉取异常';
comment on column main.corps.chat_seq is '会话存档消息游标';
comment on column main.corps.chat_last_err_msg is '会话存档上一次拉取出错的消息';
comment on column main.corps.sync_staff_time is '员工同步时间';
comment on column main.corps.sync_group_time is '群同步时间';
comment on column main.corps.sync_customer_time is '客户同步时间';
SQL;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.corps";

        migrate_exec($b, $sql);
    }
}
