<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241219072010CreateCorpCstTagLogTable
 */
final class M241219072010CreateCorpCstTagLogTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        //创建 企业客户打标签日志表
        $sql = /** @lang sql */ <<<SQL
CREATE TABLE main.corp_cst_tag_log (
  id SERIAL PRIMARY KEY,
  created_at timestamp not null default now(),
  updated_at timestamp not null default now(),

  corp_id VARCHAR(32) NOT NULL DEFAULT '',
  tag_id VARCHAR(32) NOT NULL DEFAULT '',
  external_userid VARCHAR(64) NOT NULL DEFAULT '',
  userid VARCHAR(255) NOT NULL DEFAULT '',
  source VARCHAR(64) NOT NULL DEFAULT '',
  batch_number VARCHAR(64) NOT NULL DEFAULT '',
  handle_time timestamp not null default now(),
  time_to_date INT NOT NULL DEFAULT 0,
  handle_type INT2 NOT NULL DEFAULT 0,
  status INT2 NOT NULL DEFAULT 0,
  fail_reason VARCHAR(255) NOT NULL DEFAULT ''
);

-- 添加注释
COMMENT ON COLUMN main.corp_cst_tag_log.id IS '主键ID';
COMMENT ON COLUMN main.corp_cst_tag_log.created_at IS '创建时间';
COMMENT ON COLUMN main.corp_cst_tag_log.updated_at IS '更新时间';

COMMENT ON COLUMN main.corp_cst_tag_log.corp_id IS '企业ID';
COMMENT ON COLUMN main.corp_cst_tag_log.tag_id IS '标签ID（单个标签）';
COMMENT ON COLUMN main.corp_cst_tag_log.external_userid IS '客户external_userid';
COMMENT ON COLUMN main.corp_cst_tag_log.userid IS '员工user_id';
COMMENT ON COLUMN main.corp_cst_tag_log.source IS '触发来源标识 最大64位';
COMMENT ON COLUMN main.corp_cst_tag_log.batch_number IS '打标签批次号 最大64位';
COMMENT ON COLUMN main.corp_cst_tag_log.handle_time IS '打标签时间';
COMMENT ON COLUMN main.corp_cst_tag_log.time_to_date IS 'Ymd格式';
COMMENT ON COLUMN main.corp_cst_tag_log.handle_type IS '类型 0：默认数据 1:新增标签 2:删除标签';
COMMENT ON COLUMN main.corp_cst_tag_log.status IS '状态 0:未处理，1:处理成功，2:处理失败';
COMMENT ON COLUMN main.corp_cst_tag_log.fail_reason IS '失败原因';

-- 创建索引
CREATE INDEX idx_date ON main.corp_cst_tag_log (time_to_date);
CREATE INDEX idx_corp_id ON main.corp_cst_tag_log (corp_id, tag_id);
CREATE INDEX idx_external_userid ON main.corp_cst_tag_log (external_userid);
CREATE INDEX idx_batch_number ON main.corp_cst_tag_log (batch_number);

-- 添加表注释
COMMENT ON TABLE main.corp_cst_tag_log IS '打标签日志表';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.corp_cst_tag_log";

        migrate_exec($b, $sql);
    }
}
