<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241213085932AddColumnForConversationsTable
 */
final class M241213085932AddColumnForConversationsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
alter table main.chat_conversations add column staff_last_reply_time timestamp default null;

comment on column main.chat_conversations.staff_last_reply_time is '员工最后一次回复时间';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = "alter table main.chat_conversations drop column staff_last_reply_time;";

        migrate_exec($b, $sql);
    }
}
