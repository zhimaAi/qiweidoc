<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241210082810AddColumnForCorpsTable
 */
final class M241210082810AddColumnForCorpsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql =  /** @lang sql */ <<<SQL
-- 企业信息表增加配置字段
ALTER TABLE main.corps
  ADD COLUMN show_customer_tag int2 NOT NULL DEFAULT 0,
  ADD COLUMN show_customer_tag_config jsonb NOT NULL DEFAULT '[]',
  ADD COLUMN show_is_read int2 NOT NULL DEFAULT 1;

-- 添加备注信息
COMMENT ON COLUMN main.corps.show_customer_tag IS '是否展示客户标签，0:不展示，1：展示';
COMMENT ON COLUMN main.corps.show_customer_tag_config IS '优先展示客户标签配置';
COMMENT ON COLUMN main.corps.show_is_read IS '是否展示员工已读标签，0:不展示，1展示';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
//
        $sql = /** @lang sql */ <<<SQL

-- 删除收藏字段
ALTER TABLE main.corps
    DROP COLUMN show_customer_tag,
    DROP COLUMN show_customer_tag_config,
    DROP COLUMN show_is_read;
SQL;

        migrate_exec($b, $sql);
    }
}
