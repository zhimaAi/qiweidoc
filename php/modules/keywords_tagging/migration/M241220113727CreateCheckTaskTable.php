<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241220113727CreateCheckTaskTable
 */
final class M241220113727CreateCheckTaskTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
//创建 聊天关键词规则打标签日志表
        $sql = /** @lang sql */ <<<SQL
CREATE TABLE keywords_tagging.check_task (
  id SERIAL PRIMARY KEY,
  created_at timestamp not null default now(),
  updated_at timestamp not null default now(),

  corp_id VARCHAR(32) NOT NULL DEFAULT '',
  switch INT2 NOT NULL DEFAULT 0,
  last_msg_id  varchar(191) not null default '',
  last_msg_time timestamp not null default now()
);

-- 添加注释
COMMENT ON COLUMN keywords_tagging.check_task.id IS '主键ID';
COMMENT ON COLUMN keywords_tagging.check_task.created_at IS '创建时间';
COMMENT ON COLUMN keywords_tagging.check_task.updated_at IS '更新时间';
COMMENT ON COLUMN keywords_tagging.check_task.corp_id IS '所属企业微信id';
COMMENT ON COLUMN keywords_tagging.check_task.switch IS '检测状态 0：不开启 1：开启检测';
COMMENT ON COLUMN keywords_tagging.check_task.last_msg_id IS '上次检测企业微信消息唯一id';
COMMENT ON COLUMN keywords_tagging.check_task.last_msg_time IS '上次检测消息发出时间';

-- 创建索引
CREATE INDEX  ON keywords_tagging.check_task (corp_id);
CREATE INDEX  ON keywords_tagging.check_task (last_msg_time);

-- 添加表注释
COMMENT ON TABLE keywords_tagging.check_task IS '聊天关键词检测任务表';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table keywords_tagging.check_task;";

        migrate_exec($b, $sql);

    }
}
