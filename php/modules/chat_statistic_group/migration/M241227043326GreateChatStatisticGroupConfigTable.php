<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241227043326GreateChatStatisticGroupConfigTable
 */
final class M241227043326GreateChatStatisticGroupConfigTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL

create table chat_statistic_group.config
(
    id serial primary key,
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    work_time jsonb not null default '[]',
    cst_keywords jsonb not null default '[]',
    msg_reply_sec int2 not null default 180,
    at_msg_reply_sec int2 not null default 180,
    other_effect int2 not null default 0,
    group_staff_type int2 not null default 2,
    group_staff_ids jsonb not null default '[]',
    last_stat_time int4 not null default 0
);

comment on table chat_statistic_group.config is '群聊统计规则配置';

comment on column chat_statistic_group.config.corp_id is '所属企业id';
comment on column chat_statistic_group.config.work_time is '工作时间范围设置';
comment on column chat_statistic_group.config.cst_keywords is '客户关键词列表';
comment on column chat_statistic_group.config.msg_reply_sec is '回复消息反应时间，秒';
comment on column chat_statistic_group.config.at_msg_reply_sec is 'at后回复消息反应时间，秒';
comment on column chat_statistic_group.config.other_effect is '群内其他员工回复也算已回复，0:关闭，1：开启，默认关闭';
comment on column chat_statistic_group.config.group_staff_type is '群内其他员工回复也算已回复 开启时，群成员类型，1:指定成员，2:全部成员';
comment on column chat_statistic_group.config.group_staff_ids is '群内其他员工回复也算已回复 开启时，指定群成员ID';
comment on column chat_statistic_group.config.last_stat_time is '最后统计时间';

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop table chat_statistic_group.config";

        migrate_exec($b, $sql);
    }
}
