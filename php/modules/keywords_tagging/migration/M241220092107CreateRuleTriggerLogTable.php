<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241220092107CreateRuleTriggerLogTable
 */
final class M241220092107CreateRuleTriggerLogTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        //创建 聊天关键词规则打标签日志表
        $sql = /** @lang sql */ <<<SQL
CREATE TABLE keywords_tagging.rule_trigger_log (
  id SERIAL PRIMARY KEY,
  created_at timestamp not null default now(),
  updated_at timestamp not null default now(),

  corp_id VARCHAR(32) NOT NULL DEFAULT '',
  staff_userid VARCHAR(64) NOT NULL DEFAULT '',
  external_userid VARCHAR(64) NOT NULL DEFAULT '',
  task_id  INT4 NOT NULL DEFAULT 0,
  keyword VARCHAR(64) NOT NULL DEFAULT '',
  rule_info jsonb not null default '[]',
  trigger_num INT2 NOT NULL DEFAULT 0,
  msg_id  varchar(191) not null default '',
  msg_time timestamp not null default now(),
  tag_ids VARCHAR(64) NOT NULL DEFAULT ''
);

-- 添加注释
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.id IS '主键ID';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.created_at IS '创建时间';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.updated_at IS '更新时间';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.corp_id IS '所属企业微信id';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.staff_userid IS '员工user_id';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.external_userid IS '客户external_userid';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.task_id IS '任务id';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.keyword IS '匹配关键词';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.rule_info IS '判断的规则信息';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.trigger_num IS '当前规则范围关键词触发次数';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.msg_id IS '企业微信消息唯一id';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.msg_time IS '消息发出时间';
COMMENT ON COLUMN keywords_tagging.rule_trigger_log.tag_ids IS '打标签id';

-- 创建索引
CREATE INDEX  ON keywords_tagging.rule_trigger_log (corp_id);
CREATE INDEX  ON keywords_tagging.rule_trigger_log (external_userid);
CREATE INDEX  ON keywords_tagging.rule_trigger_log (staff_userid);
CREATE INDEX  ON keywords_tagging.rule_trigger_log (task_id);
CREATE INDEX  ON keywords_tagging.rule_trigger_log (tag_ids);

-- 添加表注释
COMMENT ON TABLE keywords_tagging.rule_trigger_log IS '聊天关键词规则打标签日志表';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table keywords_tagging.rule_trigger_log;";

        migrate_exec($b, $sql);

    }
}
