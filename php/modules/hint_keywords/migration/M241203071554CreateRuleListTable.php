<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241203071554CreateRuleListTable
 */
final class M241203071554CreateRuleListTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<EOF
create table hint_keywords.rule (
    id serial primary key,
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    rule_name varchar(64) not null default '',
    chat_type int2 not null default 0,
    group_chat_type int2 not null default 1,
    group_chat_id jsonb not null default '[]',
    check_user_type int2 not null default 0,
    hint_group_ids  jsonb not null default '[]',
    hint_keywords  jsonb not null default '[]',
    hint_msg_type  jsonb not null default '[]',
    target_msg_type  jsonb not null default '[]',
    statistic_today  int4 not null default 0,
    statistic_yesterday  int4 not null default 0,
    statistic_total  int4 not null default 0,
    statistic_staff_keywords  int4 not null default 0,
    statistic_staff_msg  int4 not null default 0,
    statistic_cst_keywords  int4 not null default 0,
    statistic_cst_msg  int4 not null default 0,
    create_user_id varchar(64) not null default '',
    switch_status int4 not null default 0
);

comment on table hint_keywords.rule is '敏感词规则';

comment on column hint_keywords.rule.corp_id is '企业ID';
comment on column hint_keywords.rule.rule_name is '规则名称';
comment on column hint_keywords.rule.chat_type is '聊天会话场景，0:全部，1:单聊，2：群聊';
comment on column hint_keywords.rule.group_chat_type is '群聊类型，1:全部，2:指定群聊';
comment on column hint_keywords.rule.group_chat_id is '指定群聊ID列表';
comment on column hint_keywords.rule.check_user_type is '触发对象，0:全部，1:仅客户，2:仅员工';
comment on column hint_keywords.rule.hint_group_ids is '选择的敏感词组';
comment on column hint_keywords.rule.hint_keywords is '主动添加的敏感词';
comment on column hint_keywords.rule.hint_msg_type is '敏感消息类型';
comment on column hint_keywords.rule.target_msg_type is '触发消息类型，枚举值，text：文本，link：链接，weapp：小程序，card：名片，link_text：包含链接的文本，qr_code：二维码图片';
comment on column hint_keywords.rule.statistic_today is '今日触发消息总数';
comment on column hint_keywords.rule.statistic_yesterday is '昨日触发消息总数';
comment on column hint_keywords.rule.statistic_total is '累计触发总数，包含员工，客户触发的敏感词和敏感行为次数';
comment on column hint_keywords.rule.statistic_staff_keywords is '员工触发敏感词总数';
comment on column hint_keywords.rule.statistic_staff_msg is '员工触发敏感消息类型总数';
comment on column hint_keywords.rule.statistic_cst_keywords is '客户触发敏感词总数';
comment on column hint_keywords.rule.statistic_cst_msg is '客户触发敏感消息类型总数';
comment on column hint_keywords.rule.create_user_id is '规则创建人';
comment on column hint_keywords.rule.switch_status is '规则启用状态，0:关闭，1:开启';

EOF;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {

        $sql = /** @lang sql */ "drop table hint_keywords.rule";

        migrate_exec($b, $sql);
    }
}
