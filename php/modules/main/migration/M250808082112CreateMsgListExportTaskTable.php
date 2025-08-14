<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M250808082112CreateMsgListExportTaskTable
 */
final class M250808082112CreateMsgListExportTaskTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
create table main.chat_msg_export_task
(
    id serial primary key,
    corp_id varchar(32) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    create_userid varchar(64) not null default '',
    export_type int4 not null default 1,
    state int2 not null default 1,
    req_data jsonb not null default '[]',
    error_info jsonb not null default '[]',
    task_title varchar(191) not null default '',
    file_path text not null default ''
);

comment on column main.chat_msg_export_task.create_userid is '导出人';
comment on column main.chat_msg_export_task.export_type is '导出类型，枚举，1:会话存档';
comment on column main.chat_msg_export_task.state is '导出状态，1:未开始，2:导出中，3:已完成，4:导出失败，5:已取消，0:已删除';
comment on column main.chat_msg_export_task.req_data is '前端请求参数';
comment on column main.chat_msg_export_task.task_title is '导出文件名';
comment on column main.chat_msg_export_task.file_path is '文件下载';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "drop table main.chat_msg_export_task";
        migrate_exec($b, $sql);
    }
}
