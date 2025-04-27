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
use Common\Yii;
use LogicException;
use Modules\Main\Consumer\DownloadChatSessionBitMediasConsumer;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
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

class ChatSessionPullService
{
    private const MESSAGE_LIMIT = 1000;
    private const LARGE_FILE_THRESHOLD = 20 * 1024 * 1024; // 20MB
    private static CorpModel $corp;

    /**
     * 拉取并保存会话消息
     *
     * @throws Throwable
     */
    public static function handleMessage(CorpModel $corp): void
    {
        self::$corp = $corp;

        // 拉取消息
        $messages = self::fetchMessages();
        $lastSeq = null;
        foreach ($messages as $msg) {
            if (!empty($msg['seq'])) {
                $lastSeq = $msg['seq'];
            }

            // 过滤掉不能识别和重复的消息
            if (!self::isValidMessage($msg)) {
                continue;
            }

            //处理消息内容
            $messageData = self::processMessage($msg);
            if (!$messageData) {
                continue;
            }

            // 创建会话
            $conversation = self::saveConversation($messageData);

            // 保存消息
            $messageData->update([
                'conversation_id' => $conversation->get('id'),
                'conversation_type' => $conversation->get('type'),
            ]);

            // 下载资源
            if (in_array($messageData->get('msg_type'), ChatSessionService::ValidMediaType)) {
                if (self::isLargeFile($messageData)) { // 大文件到单独的队列中处理
                    Producer::dispatch(DownloadChatSessionBitMediasConsumer::class, ['corp' => $corp, 'message' => $messageData]);
                } else {
                    Producer::dispatch(DownloadChatSessionMediasConsumer::class, ['corp' => $corp, 'message' => $messageData]);
                }
            }

            // 广播
            Broadcast::event('chat-session-pull')->send(json_encode([
                'msg_id' => $messageData->get('id'),
                'msg_type' => $messageData->get('msg_type'),
                'from_role' => $messageData->get('from_role'),
                'to_role' => $messageData->get('to_role'),
            ]));
        }

        // 更新消息序号
        if (!empty($lastSeq)) {
            self::$corp->update(['chat_seq' => $lastSeq]);
        }
    }

    public static function isLargeFile(ChatMessageModel $message): bool
    {
        return $message->get('msg_type') === 'file' && $message->get('raw_content')['filesize'] > self::LARGE_FILE_THRESHOLD;
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

        $sdkFileId = $message->get('raw_content')['sdkfileid'] ?? '';
        $md5 = $message->get('raw_content')['md5sum'] ?? "";
        if (empty($sdkFileId)) {
            throw new LogicException("消息不完整, 缺少md5字段");
        }
        if (empty($md5)) {
            $md5 = md5($sdkFileId);
        }

        $fileName = Uuid::uuid4();
        $fileExtension = "";

        if ($message->get('msg_type') == 'file') {
            $fileName = $message->get('raw_content')['filename'] ?? 'default';
            $fileExtension = $message->get('raw_content')['fileext'] ?? 'default';
        } elseif ($message->get('msg_type') == 'image') {
            $fileName = Uuid::uuid4() . '.png';
            $fileExtension = "png";
        } elseif ($message->get('msg_type') == 'voice') {
            $fileName = Uuid::uuid4() . '.amr';
            $fileExtension = 'amr';
        } elseif ($message->get('msg_type') == 'video') {
            $fileName = Uuid::uuid4() . '.mp4';
            $fileExtension = 'mp4';
        } elseif ($message->get('msg_type') == 'emotion') {
            $type = $message->get('raw_content')['type'] ?? 2;
            $fileExtension = $type == 1 ? 'gif' : 'png';
            $fileName = Uuid::uuid4() . "." . $fileExtension;
        } elseif ($message->get('msg_type') == 'meeting_voice_call') {
            $fileName = $message->get('raw_content')['voiceid'] . '.mp3';
            $fileExtension = "mp4";
        }

        $objectKey = StorageService::generateObjectKey($fileName, $md5);
        $request = [
            'corp_id' => $corp->get('id'),
            'chat_secret' => $corp->get('chat_secret'),
            'sdk_file_id' => $sdkFileId,

            'storage_endpoint' => Yii::params()['local-storage']['endpoint'],
            'storage_region' => Yii::params()['local-storage']['region'],
            'storage_access_key' => Yii::params()['local-storage']['access_key'],
            'storage_secret_key' => Yii::params()['local-storage']['secret_key'],
            'storage_bucket_name' => StorageModel::SESSION_BUCKET,
            'storage_object_key' => $objectKey,
        ];
        $fileInfo = [];
        Yii::getNatsClient(300)->request('wxfinance.FetchAndStreamMediaData', json_encode($request), function (Payload $payload) use (&$fileInfo) {
            $fileInfo = json_decode($payload->body, true);
        });
        if (empty($fileInfo) || empty($fileInfo['hash'])) {
            throw new LogicException("下载资源失败");
        }

        $retentionDays = (int)SettingModel::getValue('local_session_file_retention_days');
        $storage = StorageModel::create([
            'hash'                          => $fileInfo['hash'],
            'original_filename'             => $fileName,
            'file_extension'                => $fileExtension,
            'mime_type'                     => $fileInfo['mime'] ?? '',
            'file_size'                     => $fileInfo['size'] ?? 0,
            'local_storage_bucket'          => StorageModel::SESSION_BUCKET,
            'local_storage_object_key'      => $objectKey,
            'local_storage_expired_at'      => $retentionDays > 0 ? Carbon::now()->addDays($retentionDays)->toDateTimeString('m') : null,
        ]);
        $message->update(['msg_content' => $fileInfo['hash']]);

        // 保存到云存储
        StorageService::saveCloud($storage);
    }

    /**
     * 从企微拉取消息
     * 由golang处理并自动解密
     */
    private static function fetchMessages()
    {
        $request = [
            'corp_id' => self::$corp->get('id'),
            'chat_secret' => self::$corp->get('chat_secret'),
            'chat_private_key' => self::$corp->get('chat_private_key'),
            'chat_public_key_version' => self::$corp->get('chat_public_key_version'),
            'chat_seq' => self::$corp->get('chat_seq'),
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
