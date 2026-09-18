<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * 将媒体获取流程状态与对象存储中的文件可用状态分离。
 */
final class M260918103000AddMediaDownloadStatusForChatMessagesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
alter table main.chat_messages
    add column media_download_status int2 not null default 5,
    add column media_download_updated_at timestamp default null;

comment on column main.chat_messages.media_download_status is '媒体获取状态：0无需下载、1待下载、2处理中、3已完成、4重试耗尽、5待归一化';
comment on column main.chat_messages.media_download_updated_at is '媒体获取状态最后更新时间';

-- 非媒体消息无需下载。
update main.chat_messages
set media_download_status = 0,
    media_download_updated_at = now()
where msg_type not in (
    'image', 'voice', 'video', 'emotion', 'file', 'meeting_voice_call',
    'chatrecord', 'mixed', 'note'
);

-- 普通媒体沿用 msg_content 作为是否曾成功下载的永久凭据。
update main.chat_messages
set media_download_status = case
        when msg_content <> '' then 3
        when download_retry_count >= 3 then 4
        else 1
    end,
    media_download_updated_at = now()
where msg_type in ('image', 'voice', 'video', 'emotion', 'file', 'meeting_voice_call');

-- 结构化消息的 content 可能是对象或 JSON 字符串，由应用按 storage_hash 分批归一化。
create index idx_chat_messages_media_download_status
    on main.chat_messages using btree (media_download_status, msg_time);

create index idx_chat_messages_media_download_pending
    on main.chat_messages using btree (corp_id, msg_time)
    where media_download_status = 1;
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
drop index if exists main.idx_chat_messages_media_download_pending;
drop index if exists main.idx_chat_messages_media_download_status;

alter table main.chat_messages
    drop column media_download_updated_at,
    drop column media_download_status;
SQL;

        migrate_exec($b, $sql);
    }
}
