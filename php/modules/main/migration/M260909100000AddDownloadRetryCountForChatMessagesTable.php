<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * 为会话消息增加媒体下载尝试次数，限制失败文件的自动补偿次数。
 */
final class M260909100000AddDownloadRetryCountForChatMessagesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
alter table main.chat_messages
    add column download_retry_count int4 not null default 0;

comment on column main.chat_messages.download_retry_count is '媒体下载已投递次数，包含首次下载，达到上限后不再自动重试';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        migrate_exec($b, 'alter table main.chat_messages drop column download_retry_count');
    }
}
