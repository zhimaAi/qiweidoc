<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241203073754CreateNoticeConfigTable
 */
final class M241203073754CreateNoticeConfigTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<EOF
create table hint_keywords.notice_config (
    id serial primary key,
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    notice_switch  int2 not null default 0,

    app_notice_switch  int2 not null default 0,
    app_notice_userid  jsonb not null default '[]',

    wechat_notice_switch  int2 not null default 0,
    wechat_notice_hook  varchar(255) not null default '',
    wechat_notice_type  int2 not null default 0,
    wechat_notice_user  jsonb not null default '[]',

    dingtalk_notice_switch  int2 not null default 0,
    dingtalk_notice_hook  varchar(255) not null default '',
    dingtalk_notice_secret  varchar(255) not null default '',
    dingtalk_notice_type  int2 not null default 0,
    dingtalk_notice_user  jsonb not null default '[]',
    statistics_msg_time timestamp default null
);

comment on table hint_keywords.notice_config is '敏感词提醒通知配置';

comment on column hint_keywords.notice_config.corp_id is '企业ID';
comment on column hint_keywords.notice_config.notice_switch is '通知开关状态，0：关闭，1：开启';
comment on column hint_keywords.notice_config.app_notice_switch is '企微应用通知开关状态，0：关闭，1：开启';
comment on column hint_keywords.notice_config.app_notice_userid is '通知的员工ID';
comment on column hint_keywords.notice_config.wechat_notice_switch is '微信群通知开关状态，0：关闭，1:开启';
comment on column hint_keywords.notice_config.wechat_notice_hook is '微信群机器人推送地址';
comment on column hint_keywords.notice_config.wechat_notice_type is '@人类型，0:不@，1:@全部，2:@指定群成员';
comment on column hint_keywords.notice_config.wechat_notice_user is '@指定群成员用户ID';
comment on column hint_keywords.notice_config.dingtalk_notice_switch is '钉钉群通知开关，0:关闭，1：开启';
comment on column hint_keywords.notice_config.dingtalk_notice_hook is '钉钉群推送地址';
comment on column hint_keywords.notice_config.dingtalk_notice_secret is '钉钉群推送加密密钥';
comment on column hint_keywords.notice_config.dingtalk_notice_type is '@人类型，0:不@，1:@全部，2:@指定人手机号';
comment on column hint_keywords.notice_config.dingtalk_notice_user is '@指定用户列表';
comment on column hint_keywords.notice_config.statistics_msg_time is '统计敏感词的最后消息时间';

EOF;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {

        $sql = /** @lang sql */ "drop table hint_keywords.notice_config";

        migrate_exec($b, $sql);
    }
}
