<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M250805102641AddColumnsToChatStatisticGroupDetailTable
 */
final class M250805102641AddColumnsToChatStatisticGroupDetailTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql =  /** @lang sql */ <<<SQL
-- 添加字段到统计明细表
ALTER TABLE chat_statistic_group.detail
  ADD COLUMN staff_self_msg_num int4 NOT NULL DEFAULT 0;

-- 添加备注信息
COMMENT ON COLUMN chat_statistic_group.detail.staff_self_msg_num IS '统计员工自己发送的消息数';
SQL;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
-- 删除字段
ALTER TABLE chat_statistic_group.detail
    DROP COLUMN staff_self_msg_num;
SQL;

        migrate_exec($b, $sql);
    }
}
