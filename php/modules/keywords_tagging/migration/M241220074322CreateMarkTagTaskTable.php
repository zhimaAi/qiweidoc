<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241220074322CreateMarkTagTaskTable
 */
final class M241220074322CreateMarkTagTaskTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        //创建 企业客户打标签日志表
        $sql = /** @lang sql */ <<<SQL
CREATE TABLE keywords_tagging.mark_tag_task (
  id SERIAL PRIMARY KEY,
  created_at timestamp not null default now(),
  updated_at timestamp not null default now(),
  corp_id VARCHAR(32) NOT NULL DEFAULT '0',
  check_type INT2 NOT NULL DEFAULT 1,
  check_chat_type INT2 NOT NULL DEFAULT 1,
  name VARCHAR(32) NOT NULL DEFAULT '',
  staff_userid_list jsonb not null default '[]',
  partial_match jsonb not null default '[]',
  full_match jsonb not null default '[]',
  rules_list jsonb not null default '[]',
  switch INT2 NOT NULL DEFAULT 0,
  del_status INT2 NOT NULL DEFAULT 0
);

-- 添加注释
COMMENT ON COLUMN keywords_tagging.mark_tag_task.id IS '主键ID';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.created_at IS '创建时间';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.updated_at IS '更新时间';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.corp_id IS '所属企业微信id';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.check_type IS '生效用户 1:仅客户 2:仅员工 3:客户和员工';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.check_chat_type IS '生效场景 1:仅单聊 2:仅群聊';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.name IS '任务名称';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.staff_userid_list IS '员工user_id数组';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.partial_match IS '模糊匹配关键词数组';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.full_match IS '精准匹配关键词数组';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.rules_list IS '规则列表数组';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.switch IS '开关 0 关 1 开';
COMMENT ON COLUMN keywords_tagging.mark_tag_task.del_status IS '删除标识 0 未删除 1 删除';

-- 创建索引
CREATE INDEX  ON keywords_tagging.mark_tag_task (corp_id);

-- 添加表注释
COMMENT ON TABLE keywords_tagging.mark_tag_task IS '聊天关键词自动打标签任务表';

SQL;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table keywords_tagging.mark_tag_task;";

        migrate_exec($b, $sql);
    }
}
