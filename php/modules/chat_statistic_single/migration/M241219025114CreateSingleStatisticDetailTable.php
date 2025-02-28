<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241219025114CreateSingleStatisticDetailTable
 */
final class M241219025114CreateSingleStatisticDetailTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql = /** @lang sql */
            <<<SQL
create table chat_statistic_single.detail
(
    corp_id varchar(64) not null default '',
    date_no int4 not null default 0,
    stat_time timestamp not null default now(),
    staff_user_id varchar(64) not null default '',
    external_userid varchar(64) not null default '',
    staff_msg_no_work int4 not null default 0,
    cst_msg_no_work int4 not null default 0,
    staff_msg_no_day int4 not null default 0,
    cst_msg_no_day int4 not null default 0,
    round_no int4 not null default 0,
    recover_in_time int4 not null default 0,
    first_recover_time int4 not null default 0,
    reply_status int4 not null default 0,
    promoter_type int4 not null default 0,
    last_msg_id varchar(191) not null default '',
    is_new_user int2 not null default 0,
    conversation_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now()
);

create index on chat_statistic_single.detail using btree ("corp_id", "date_no","staff_user_id");
create index on chat_statistic_single.detail using btree ("promoter_type");

comment on table chat_statistic_single.detail is '单聊统计明细';

comment on column chat_statistic_single.detail.corp_id is '所属企业id';
comment on column chat_statistic_single.detail.date_no is '统计所属日期，20241129';
comment on column chat_statistic_single.detail.stat_time is '统计所属时间，分表';
comment on column chat_statistic_single.detail.staff_user_id is '员工ID';
comment on column chat_statistic_single.detail.external_userid is '客户ID';
comment on column chat_statistic_single.detail.staff_msg_no_work is '员工消息数汇总，工作时间';
comment on column chat_statistic_single.detail.cst_msg_no_work is '客户消息数汇总，工作时间';
comment on column chat_statistic_single.detail.staff_msg_no_day is '员工消息数汇总，当日';
comment on column chat_statistic_single.detail.cst_msg_no_day is '客户消息总数，当日';
comment on column chat_statistic_single.detail.recover_in_time is '指定时间内回复次数';
comment on column chat_statistic_single.detail.round_no is '对话轮次，客户发起，员工回复为一轮';
comment on column chat_statistic_single.detail.first_recover_time is '首次回复时长';
comment on column chat_statistic_single.detail.reply_status is '回复状态，0:未回复，1:已回复';
comment on column chat_statistic_single.detail.promoter_type is '发起人类型  1:员工，2:客户';
comment on column chat_statistic_single.detail.last_msg_id is '最后一条消息ID';
comment on column chat_statistic_single.detail.is_new_user is '是否为新客户，0:不是，1:是';
comment on column chat_statistic_single.detail.conversation_id is '会话ID';

SELECT create_hypertable('chat_statistic_single.detail', 'stat_time', chunk_time_interval => INTERVAL '1 month');

SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */
            "drop table chat_statistic_single.detail";

        migrate_exec($b, $sql);
    }
}
