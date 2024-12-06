<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241129063822AddColumnsForChatConversationsTable
 */
final class M241129063822AddColumnsForChatConversationsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql =  /** @lang sql */ <<<SQL
-- 添加会话表
ALTER TABLE main.chat_conversations
  ADD COLUMN is_collect int2 NOT NULL DEFAULT 0,
  ADD COLUMN collect_reason varchar(255) NOT NULL DEFAULT '',
  ADD COLUMN collect_time timestamp(6);

-- 添加备注信息
COMMENT ON COLUMN main.chat_conversations.is_collect IS '是否为收藏 1收藏 0不收藏';
COMMENT ON COLUMN main.chat_conversations.collect_reason IS '收藏原因';
COMMENT ON COLUMN main.chat_conversations.collect_time IS '收藏时间戳';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        //
        $sql = /** @lang sql */ <<<SQL

-- 删除收藏字段
ALTER TABLE main.chat_conversations
    DROP COLUMN is_collect,
    DROP COLUMN collect_reason,
    DROP COLUMN collect_time;
SQL;

        migrate_exec($b, $sql);

    }
}
