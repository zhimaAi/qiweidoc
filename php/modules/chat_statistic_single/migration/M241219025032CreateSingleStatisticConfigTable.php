<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241219025032CreateSingleStatisticConfigTable
 */
final class M241219025032CreateSingleStatisticConfigTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            <<<SQL

create table chat_statistic_single.config
(
    id serial primary key,
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    work_time jsonb not null default '[]',
    cst_keywords jsonb not null default '[]',
    staff_keywords jsonb not null default '[]',
    msg_reply_sec int2 not null default 180,
    last_stat_time int4 not null default 0
);

comment on table chat_statistic_single.config is '单聊统计规则配置';

comment on column chat_statistic_single.config.corp_id is '所属企业id';
comment on column chat_statistic_single.config.work_time is '工作时间范围设置';
comment on column chat_statistic_single.config.cst_keywords is '客户关键词列表';
comment on column chat_statistic_single.config.staff_keywords is '员工关键词列表';
comment on column chat_statistic_single.config.msg_reply_sec is '回复消息反应时间，秒';
comment on column chat_statistic_single.config.last_stat_time is '最后统计时间';

SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop table chat_statistic_single.config";

        migrate_exec($b, $sql);
    }

}
