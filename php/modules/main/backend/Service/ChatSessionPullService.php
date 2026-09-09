<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 会话存档消息拉取服务
 */

namespace Modules\Main\Service;

use Basis\Nats\Message\Payload;
use Carbon\Carbon;
use Common\Broadcast;
use Common\Job\Producer;
use Common\Micro;
use Common\Yii;
use LogicException;
use Modules\Main\Consumer\DownloadChatSessionBitMediasConsumer;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
use Modules\Main\Consumer\UploadStorageToCloudConsumer;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\SettingModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\StorageModel;
use Ramsey\Uuid\Uuid;
use Throwable;
use Yiisoft\Db\Expression\Expression;

class ChatSessionPullService
{
    private const MESSAGE_LIMIT = 100;
    private const MAX_FETCH_ROUNDS = 10;
    private const LARGE_FILE_THRESHOLD = 20 * 1024 * 1024; // 20MB
    public const MAX_MEDIA_DOWNLOAD_ATTEMPTS = 3;
    private const MEDIA_SDK_CHUNK_TIMEOUT = 30;
    private const MEDIA_GO_TIMEOUT = 3300;
    private const MEDIA_NATS_TIMEOUT = 3600;
    private const MEDIA_LOCK_TTL = 3900;
    private const MAX_CHAT_RECORD_DEPTH = 3;
    private const CHAT_RECORD_MEDIA_TYPE_MAP = [
        'ChatRecordImage' => 'image',
        'ChatRecordFile' => 'file',
        'ChatRecordVideo' => 'video',
        'ChatRecordVoice' => 'voice',
        'ChatRecordEmotion' => 'emotion',
        'image' => 'image',
        'file' => 'file',
        'video' => 'video',
        'voice' => 'voice',
        'emotion' => 'emotion',
    ];
    private static CorpModel $corp;

    /**
     * 拉取并保存会话消息
     *
     * @throws Throwable
     */
    public static function handleMessage(CorpModel $corp): void
    {
        self::$corp = $corp;

        $chatSeq = (int) self::$corp->get('chat_seq');
        for ($round = 0; $round < self::MAX_FETCH_ROUNDS; $round++) {
            $messages = self::fetchMessages($chatSeq);
            if (empty($messages)) {
                break;
            }

            $lastSeq = null;
            foreach ($messages as $msg) {
                if (!empty($msg['seq'])) {
                    $lastSeq = $msg['seq'];
                }

                // 过滤掉不能识别和重复的消息
                if (!self::isValidMessage($msg)) {
                    continue;
                }

                //处理并保存消息内容
                $messageData = self::processMessage($msg);
                if (!$messageData) {
                    continue;
                }

                Yii::logger()->info("保存消息成功", ['msg_content' => $messageData->get('msg_content'), 'msg_type' => $messageData->get('msg_type')]);

                // 创建会话
                $conversation = self::saveConversation($messageData);

                // 更新消息的会话信息
                $messageData->update([
                    'conversation_id' => $conversation->get('id'),
                    'conversation_type' => $conversation->get('type'),
                ]);

                // 下载资源
                if (in_array($messageData->get('msg_type'), ChatSessionService::ValidMediaType)) {
                    self::dispatchMediaDownload($corp, $messageData);
                } elseif (in_array($messageData->get('msg_type'), [
                    EnumMessageType::ChatRecord->value,
                    EnumMessageType::Mixed->value,
                ], true)) {
                    self::dispatchMediaDownload($corp, $messageData);
                }

                // 广播
                Broadcast::event('chat-session-pull')->send(json_encode([
                    'msg_id' => $messageData->get('id'),
                    'msg_type' => $messageData->get('msg_type'),
                    'from_role' => $messageData->get('from_role'),
                    'to_role' => $messageData->get('to_role'),
                ]));
            }

            // 每批处理完成后推进游标，避免下一批重复拉取。
            if (!empty($lastSeq)) {
                $chatSeq = (int) $lastSeq;
                self::$corp->update(['chat_seq' => $chatSeq]);
            }

            if (count($messages) < self::MESSAGE_LIMIT) {
                break;
            }
        }
    }

    public static function isLargeFile(ChatMessageModel $message): bool
    {
        return in_array($message->get('msg_type'), ['file', 'video'], true)
            && (int) ($message->get('raw_content')['filesize'] ?? 0) > self::LARGE_FILE_THRESHOLD;
    }

    /**
     * 原子记录一次媒体下载投递，并把任务加入对应队列。
     *
     * 下载次数包含首次投递，达到上限后不再自动投递，避免补偿任务持续下载同一个失败文件。
     * 使用带条件的 UPDATE，避免实时拉取和定时补偿同时处理同一消息时重复无限入队。
     *
     * @throws Throwable
     */
    public static function dispatchMediaDownload(CorpModel $corp, ChatMessageModel $message): void
    {
        $messageType = $message->get('msg_type');
        $isStructuredMessage = in_array($messageType, [
            EnumMessageType::ChatRecord->value,
            EnumMessageType::Mixed->value,
        ], true);

        $query = ChatMessageModel::query()
            ->where(['msg_id' => $message->get('msg_id')])
            ->andWhere(['<', 'download_retry_count', self::MAX_MEDIA_DOWNLOAD_ATTEMPTS]);

        // 普通媒体下载完成后会写入 msg_content；结构化消息使用 raw_content.storage_hash，
        // msg_content 可能本来就有文本内容，因此不能用 msg_content 作为它的完成标记。
        if (!$isStructuredMessage) {
            $query->andWhere(['msg_content' => '']);
        }

        $currentRetryCount = (int) $message->get('download_retry_count');
        $updated = $query->update([
            'download_retry_count' => new Expression('download_retry_count + 1'),
        ]);
        if ($updated !== 1) {
            Yii::logger()->debug('跳过会话媒体下载投递', [
                'msg_id' => $message->get('msg_id'),
                'download_retry_count' => $currentRetryCount,
                'max_attempts' => self::MAX_MEDIA_DOWNLOAD_ATTEMPTS,
            ]);
            return;
        }

        // 让序列化到队列中的模型带上最新次数，便于日志和后续诊断。
        $message->set('download_retry_count', $currentRetryCount + 1);

        if (self::isLargeFile($message)) { // 大文件到单独的队列中处理
            Producer::dispatch(DownloadChatSessionBitMediasConsumer::class, ['corp' => $corp, 'message' => $message]);
            return;
        }

        Producer::dispatch(DownloadChatSessionMediasConsumer::class, ['corp' => $corp, 'message' => $message]);
    }

    /**
     * 下载并保存资源
     *
     * @throws Throwable
     */
    public static function handleMedia(CorpModel $corp, ChatMessageModel $message)
    {
        if (!in_array($message->get('msg_type'), ChatSessionService::ValidMediaType)) {
            throw new LogicException("消息类型不正确");
        }

        $hash = self::downloadMedia($corp, $message->get('msg_type'), $message->get('raw_content'));
        $message->update(['msg_content' => $hash]);
    }

    /**
     * 下载聊天记录或混合消息中最多三层的媒体，并把存储 hash 回写到对应 content。
     *
     * @throws Throwable
     */
    public static function handleStructuredMessageMedias(CorpModel $corp, ChatMessageModel $message): void
    {
        if (!in_array($message->get('msg_type'), [
            EnumMessageType::ChatRecord->value,
            EnumMessageType::Mixed->value,
        ], true)) {
            throw new LogicException('消息类型不正确');
        }

        $rawContent = $message->get('raw_content');
        if (empty($rawContent['item']) || !is_array($rawContent['item'])) {
            return;
        }

        $rawContent['item'] = self::downloadChatRecordItems($corp, $rawContent['item'], 1);
        $message->update(['raw_content' => $rawContent]);
    }

    /**
     * @throws Throwable
     */
    private static function downloadChatRecordItems(CorpModel $corp, array $items, int $depth): array
    {
        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $contentWasString = is_string($item['content'] ?? null);
            $content = self::decodeChatRecordItemContent($item['content'] ?? null);
            if ($content === null) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');
            if (isset(self::CHAT_RECORD_MEDIA_TYPE_MAP[$type])) {
                $sdkFileId = $content['sdkfileid'] ?? '';
                $md5 = (string) ($content['md5sum'] ?? '');
                if (!empty($sdkFileId) && is_md5($md5)) {
                    $content['storage_hash'] = self::downloadMedia(
                        $corp,
                        self::CHAT_RECORD_MEDIA_TYPE_MAP[$type],
                        $content,
                    );
                }
            } elseif ($depth < self::MAX_CHAT_RECORD_DEPTH && self::isChatRecordContainer($type)) {
                if (!empty($content['item']) && is_array($content['item'])) {
                    $content['item'] = self::downloadChatRecordItems($corp, $content['item'], $depth + 1);
                }
            }

            $item['content'] = $contentWasString
                ? json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : $content;
        }
        unset($item);

        return $items;
    }

    private static function decodeChatRecordItemContent(mixed $content): ?array
    {
        if (is_array($content)) {
            return $content;
        }
        if (!is_string($content) || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function isChatRecordContainer(string $type): bool
    {
        return in_array($type, ['chatrecord', 'ChatRecord', 'ChatRecordMixed', 'mixed'], true);
    }

    /**
     * 下载媒体。相同 MD5 的文件在分布式锁内复用已有存储，避免重复下载。
     *
     * @throws Throwable
     */
    private static function downloadMedia(CorpModel $corp, string $messageType, array $rawContent): string
    {
        $sdkFileId = $rawContent['sdkfileid'] ?? '';
        $md5 = strtolower((string) ($rawContent['md5sum'] ?? ''));
        if (empty($sdkFileId)) {
            throw new LogicException('消息不完整, 缺少sdkfileid字段');
        }
        if (empty($md5)) {
            $md5 = md5($sdkFileId);
        }

        $fileName = Uuid::uuid4();
        $fileExtension = "";

        if ($messageType == 'file') {
            $fileName = $rawContent['filename'] ?? 'default';
            $fileExtension = $rawContent['fileext'] ?? 'default';
        } elseif ($messageType == 'image') {
            $fileName = Uuid::uuid4() . '.png';
            $fileExtension = "png";
        } elseif ($messageType == 'voice') {
            $fileName = Uuid::uuid4() . '.amr';
            $fileExtension = 'amr';
        } elseif ($messageType == 'video') {
            $fileName = Uuid::uuid4() . '.mp4';
            $fileExtension = 'mp4';
        } elseif ($messageType == 'emotion') {
            $type = $rawContent['type'] ?? 2;
            $fileExtension = $type == 1 ? 'gif' : 'png';
            $fileName = Uuid::uuid4() . "." . $fileExtension;
        } elseif ($messageType == 'meeting_voice_call') {
            $fileName = ($rawContent['voiceid'] ?? Uuid::uuid4()) . '.mp3';
            $fileExtension = "mp3";
        }

        $mutex = Yii::mutex(self::MEDIA_LOCK_TTL);
        $mutexKey = 'chat-media-download:' . $md5;
        if (!$mutex->acquire($mutexKey, 10)) {
            throw new LogicException('相同文件正在下载，请稍后重试');
        }

        try {
            if (StorageService::hasAvailableStorage($md5)) {
                return $md5;
            }

            // 先恢复“MinIO 已完成上传但数据库未落库”的孤儿对象，避免补偿任务重复下载大文件。
            // 仅在企业微信提供了真实 MD5 和文件大小时尝试恢复，缺少校验依据时继续走正常下载。
            $expectedSize = (int) ($rawContent['filesize'] ?? 0);
            $recoverableObject = !empty($rawContent['md5sum'])
                ? StorageService::findRecoverableSessionObject($md5, $expectedSize)
                : null;
            if ($recoverableObject !== null) {
                // 使用已经完成且通过大小、MD5 校验的 MinIO 对象补建 storage 记录。
                $recoveredFileName = basename($recoverableObject['object_key']);
                $recoveredExtension = pathinfo($recoveredFileName, PATHINFO_EXTENSION) ?: $fileExtension;
                $storage = self::createStorageRecord(
                    hash: $md5,
                    fileName: $recoveredFileName,
                    fileExtension: $recoveredExtension,
                    mimeType: $recoverableObject['mime_type'],
                    fileSize: $recoverableObject['file_size'],
                    objectKey: $recoverableObject['object_key'],
                );
                Yii::logger()->info('恢复会话存档孤儿文件成功', [
                    'hash' => $md5,
                    'object_key' => $recoverableObject['object_key'],
                ]);

                // 返回 MD5 后由 handleMedia 统一更新消息 msg_content，后续扫描将直接复用该记录。
                return $storage->get('hash');
            }

            // MinIO 中不存在可恢复的完整对象时，才创建新对象 Key 并从企业微信重新下载。
            $objectKey = StorageService::generateObjectKey((string) $fileName, $md5);
            $request = [
                'corp_id' => $corp->get('id'),
                'chat_secret' => $corp->get('chat_secret'),
                'sdk_file_id' => $sdkFileId,
                'timeout' => self::MEDIA_SDK_CHUNK_TIMEOUT,
                'overall_timeout' => self::MEDIA_GO_TIMEOUT,

                'storage_endpoint' => Yii::params()['local-storage']['endpoint'],
                'storage_region' => Yii::params()['local-storage']['region'],
                'storage_access_key' => Yii::params()['local-storage']['access_key'],
                'storage_secret_key' => Yii::params()['local-storage']['secret_key'],
                'storage_bucket_name' => StorageModel::SESSION_BUCKET,
                'storage_object_key' => $objectKey,
            ];
            $fileInfo = Micro::call(
                'wxfinance',
                'FetchAndStreamMediaData',
                json_encode($request),
                self::MEDIA_NATS_TIMEOUT,
            );
            if (empty($fileInfo) || empty($fileInfo['hash'])) {
                throw new LogicException('下载资源失败');
            }

            $actualHash = strtolower($fileInfo['hash']);
            if (!empty($rawContent['md5sum']) && $actualHash !== $md5) {
                self::removeInvalidMedia($objectKey);
                throw new LogicException('下载资源MD5校验失败');
            }

            // 下载成功与孤儿对象恢复共用同一落库方法，保证 storage 字段和云端同步行为一致。
            self::createStorageRecord(
                hash: $actualHash,
                fileName: (string) $fileName,
                fileExtension: $fileExtension,
                mimeType: $fileInfo['mime'] ?? '',
                fileSize: (int) ($fileInfo['size'] ?? 0),
                objectKey: $objectKey,
            );
            return $actualHash;
        } finally {
            $mutex->release($mutexKey);
        }
    }

    /**
     * 统一创建会话文件存储记录，并在落库后触发云存储同步。
     *
     * @throws Throwable
     */
    private static function createStorageRecord(
        string $hash,
        string $fileName,
        string $fileExtension,
        string $mimeType,
        int $fileSize,
        string $objectKey,
    ): StorageModel {
        // 恢复孤儿对象时从当前时间重新计算本地保留期限，避免刚恢复就被清理任务删除。
        $retentionDays = (int) SettingModel::getValue('local_session_file_retention_days');
        $storage = StorageModel::create([
            'hash' => $hash,
            'original_filename' => $fileName,
            'file_extension' => $fileExtension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'local_storage_bucket' => StorageModel::SESSION_BUCKET,
            'local_storage_object_key' => $objectKey,
            'local_storage_expired_at' => $retentionDays > 0 ? Carbon::now()->addDays($retentionDays)->toDateTimeString('m') : null,
        ]);

        // storage 记录创建成功后再派发云存储任务，确保消费者始终能查询到本地源对象信息。
        Producer::dispatch(UploadStorageToCloudConsumer::class, ['storage' => $storage]);
        return $storage;
    }

    private static function removeInvalidMedia(string $objectKey): void
    {
        try {
            StorageService::getLocalS3Client()->deleteObject([
                'Bucket' => StorageModel::SESSION_BUCKET,
                'Key' => $objectKey,
            ]);
        } catch (Throwable $e) {
            Yii::logger()->error($e);
        }
    }

    /**
     * 从企微拉取消息
     * 由golang处理并自动解密
     */
    private static function fetchMessages(int $chatSeq)
    {
        $request = [
            'corp_id' => self::$corp->get('id'),
            'chat_secret' => self::$corp->get('chat_secret'),
            'chat_private_key' => self::$corp->get('chat_private_key'),
            'chat_public_key_version' => self::$corp->get('chat_public_key_version'),
            'chat_seq' => $chatSeq,
            'limit' => self::MESSAGE_LIMIT,
        ];

        $result = [];
        Yii::getNatsClient()->request('wxfinance.FetchData', json_encode($request), function (Payload $payload) use (&$result) {
            $result = json_decode($payload->body, true);
        });
        return $result;
    }

    /**
     * 检查消息格式是否合法以及消息是否存在
     * @throws Throwable
     */
    private static function isValidMessage(array $msg): bool
    {
        // 缺少字段的忽略
        if (empty($msg['decrypted_data']) || empty($msg['msgid']) || empty($msg['seq'])) {
            return false;
        }

        // 解密失败的忽略
        $decryptedData = json_decode($msg['decrypted_data'], true);
        if (empty($decryptedData['msgtime']) || empty($decryptedData['from']) || empty($decryptedData['tolist'])) {
            return false;
        }

        // 重复消息忽略
        $old = ChatMessageModel::query()
            ->where(['and',
                ['msg_id' => $msg['msgid']],
                ['msg_time' => Carbon::createFromTimestampMsUTC($decryptedData['msgtime'])->timezone('Asia/Shanghai')->format('Y-m-d H:i:s.v')],
            ])
            ->getOne();
        if (!empty($old)) {
            return false;
        }

        // 不在会话存档中的员工的消息忽略掉
        $inArchive = false;
        $validStaffList = StaffModel::query()
            ->select('userid')
            ->where(["chat_status" => 1])
            // ->andWhere(['enable_archive' => true])
            ->all();
        $validStaffList = array_column($validStaffList, 'userid');
        if (in_array($decryptedData['from'], $validStaffList)) {
            $inArchive = true;
        }
        foreach ($decryptedData['tolist'] as $to) {
            if (in_array($to, $validStaffList)) {
                $inArchive = true;
                break;
            }
        }
        if (!$inArchive) {
            return false;
        }

        return true;
    }

    /**
     * 保存会话
     * @throws Throwable
     */
    private static function saveConversation(ChatMessageModel $messageData): ChatConversationsModel
    {
        $idList = [self::$corp->get('id')];
        if (!empty($messageData->get('roomid'))) {
            $type = EnumChatConversationType::Group;
            $idList[] = $messageData->get('roomid');

            // 群聊消息：确保群信息已写入（不存在则查询企微内部群接口补全）
            GroupModel::ensureGroupExists(self::$corp, $messageData->get('roomid'));
        } else {
            if (self::checkIsExternal($messageData->get('from'), $messageData->get('to_list')[0])) {
                $type = EnumChatConversationType::Single;
            } else {
                $type = EnumChatConversationType::Internal;
            }
            $idList[] = $messageData->get('from');
            $idList[] = $messageData->get('to_list')[0];
        }
        sort($idList);
        $id = md5(implode('', $idList));

        $conversation = ChatConversationsModel::query()->where(['id' => $id])->getOne();
        if (empty($conversation)) {
            if (self::hasExternalPrefix($messageData->get('from'))) {
                $fromRole = EnumChatMessageRole::Customer;
                CustomersModel::hasConversationSave(self::$corp, $messageData->get('from'));
            } else {
                $fromRole = EnumChatMessageRole::Staff;
                StaffModel::hasConversationSave(self::$corp, $messageData->get('from'));
            }

            if ($type == EnumChatConversationType::Group) {
                $toRole = EnumChatMessageRole::Group;
                GroupModel::hasConversationSave(self::$corp, $messageData->get('roomid'));
            } else {
                if (self::hasExternalPrefix($messageData->get('to_list')[0])) {
                    $toRole = EnumChatMessageRole::Customer;
                    CustomersModel::hasConversationSave(self::$corp, $messageData->get('to_list')[0]);
                } else {
                    $toRole = EnumChatMessageRole::Staff;
                    StaffModel::hasConversationSave(self::$corp, $messageData->get('to_list')[0]);
                }
            }

            $data = [
                'id' => $id,
                'corp_id' => self::$corp->get('id'),
                'type' => $type,
                'from' => $messageData->get('from'),
                'from_role' => $fromRole,
                'to' => $type == EnumChatConversationType::Group ? $messageData->get('roomid') : $messageData->get('to_list')[0],
                'to_role' => $toRole,
                'last_msg_time' => $messageData->get('msg_time'),
            ];
            if ($data['from_role'] == EnumChatMessageRole::Staff) {
                $data['staff_last_reply_time'] = Carbon::now()->format('Y-m-d H:i:s.v');
            }
            $conversation = ChatConversationsModel::create($data);
        } else {
            $data = ['last_msg_time' => $messageData->get('msg_time')];
            if ($messageData->get('from_role') == EnumChatMessageRole::Staff) {
                $data['staff_last_reply_time'] = Carbon::now()->format('Y-m-d H:i:s.v');
            }
            $conversation->update($data);
        }

        return $conversation;
    }

    /**
     * 对消息内容进行处理
     * 统一成能够被保存到数据库中的字段格式
     * @throws Throwable
     */
    private static function processMessage(array $msg): ?ChatMessageModel
    {
        $decryptedData = json_decode($msg['decrypted_data'], true);
        $msgType = $decryptedData['msgtype'] ?? '';
        $enumMsgType = EnumMessageType::tryFrom($msgType);
        if (!$enumMsgType) {
            Yii::logger()->warning('不支持的会话消息类型，消息未入库', [
                'msg_id' => $msg['msgid'] ?? '',
                'seq' => $msg['seq'] ?? 0,
                'msg_type' => $msgType,
            ]);
            return null;
        }
        $content = $enumMsgType->getMessageHandler()($decryptedData);

        $messageData = ChatMessageModel::create(array_merge([
            'corp_id' => self::$corp->get('id'),
            'msg_id' => $msg['msgid'],
            'seq' => $msg['seq'],
            'public_key_ver' => self::$corp->get('chat_public_key_version'),
            'action' => $decryptedData['action'] ?? '',
            'from' => $decryptedData['from'] ?? '',
            'to_list' => $decryptedData['tolist'] ?? [],
            'msg_type' => $decryptedData['msgtype'] ?? '',
            'roomid' => $decryptedData['roomid'] ?? '',
            'msg_time' => Carbon::createFromTimestampMsUTC($decryptedData['msgtime'])->timezone('Asia/Shanghai')->format('Y-m-d H:i:s.v'),
        ], $content));
        if (self::hasExternalPrefix($messageData->get('from'))) {
            $messageData->set('from_role', EnumChatMessageRole::Customer);
        } else {
            $messageData->set('from_role', EnumChatMessageRole::Staff);
        }

        if (!empty($messageData->get('roomid'))) {
            $messageData->set('to_role', EnumChatMessageRole::Group);
        } else {
            if (self::hasExternalPrefix($messageData->get('to_list')[0])) {
                $messageData->set('to_role', EnumChatMessageRole::Customer);
            } else {
                $messageData->set('to_role', EnumChatMessageRole::Staff);
            }
        }
        $messageData->save();

        return $messageData;
    }

    public static function hasExternalPrefix($id): bool
    {
        $prefixList = ['wo', 'wm'];
        foreach ($prefixList as $prefix) {
            if (str_starts_with($id, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否为外部联系人聊天
     */
    public static function checkIsExternal(string $from, string $to): bool
    {
        $length = 32;
        foreach ([[$from, $length], [$to, $length]] as [$id, $length]) {
            if (strlen($id) === $length && self::hasExternalPrefix($id)) {
                return true;
            }
        }

        return false;
    }
}
