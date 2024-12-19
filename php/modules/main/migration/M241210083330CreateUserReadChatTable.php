<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241210083330CreateUserReadChatTable
 */
final class M241210083330CreateUserReadChatTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL
create table main.user_read_chat_detail
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    corp_id varchar(32) not null default '[]',
    users_id int4 not null default 0,
    conversation_id varchar(32) not null default 0
);

comment on table main.user_read_chat_detail is '账户是否已读会话';

create index on main.user_read_chat_detail using btree ("users_id","conversation_id");


comment on column main.user_read_chat_detail.corp_id is '所属企业id';
comment on column main.user_read_chat_detail.users_id is '账户ID';
comment on column main.user_read_chat_detail.conversation_id is '会话ID';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop table main.user_read_chat_detail;";

        migrate_exec($b, $sql);
    }
}
