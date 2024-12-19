<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241210095953CreateReplyRuleTable
 */
final class M241210095953CreateReplyRuleTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table timeout_reply_single.reply_rule
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(64) not null default '',
    filter_full_match_word_list jsonb not null default '[]',
    filter_half_match_word_list jsonb not null default '[]',
    include_image_msg bool not null default false,
    include_emoji_msg bool not null default false,
    include_emoticons_msg bool not null default false,
    working_hours jsonb not null default '{}'
);

create index on timeout_reply_single.reply_rule using btree (corp_id);

comment on table timeout_reply_single.reply_rule is '回复规则';
comment on column timeout_reply_single.reply_rule.corp_id is '企业id';
comment on column timeout_reply_single.reply_rule.filter_full_match_word_list is '过滤全匹配关键词列表';
comment on column timeout_reply_single.reply_rule.filter_half_match_word_list is '过滤半匹配关键词列表';
comment on column timeout_reply_single.reply_rule.include_image_msg is '是否包含图片';
comment on column timeout_reply_single.reply_rule.include_emoji_msg is '是否包含emoji';
comment on column timeout_reply_single.reply_rule.include_emoticons_msg is '是否包含表情包';
comment on column timeout_reply_single.reply_rule.working_hours is '工作时间段';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table timeout_reply_single.reply_rule";

        migrate_exec($b, $sql);
    }
}
