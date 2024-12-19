<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 会话存档消息拉取服务
 */

namespace Modules\Main\Service;

use Carbon\Carbon;
use Common\Broadcast;
use Common\Job\Producer;
use Common\Yii;
use Exception;
use Modules\Main\Consumer\DownloadChatSessionMediasConsumer;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Ramsey\Uuid\Uuid;
use Throwable;

class ChatSessionPullService
{
    private const MESSAGE_LIMIT = 1000;
    private static CorpModel $corp;
    const ValidMediaType = [
        'image',
        'voice',
        'video',
        'emotion',
        'file',
        'meeting_voice_call',
    ];

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

            // 创建会话
            $conversation = self::saveConversation($messageData);

            // 保存消息到数据库
            $messageData->update([
                'conversation_id' => $conversation->get('id'),
                'conversation_type' => $conversation->get('type'),
            ]);

            // 下载资源
            if (in_array($messageData->get('msg_type'), self::ValidMediaType)) {
                Producer::dispatch(DownloadChatSessionMediasConsumer::class, ['corp' => $corp, 'message' => $messageData]);
            }

            // 广播
            Broadcast::event('chat-session-pull')->send(json_encode([$messageData->toArray()]));
        }

        // 更新消息序号
        if (!empty($lastSeq)) {
            self::$corp->update(['chat_seq' => $lastSeq]);
        }
    }

    /**
     * 下载并保存资源
     *
     * @throws Throwable
     */
    public static function handleMedia(CorpModel $corp, ChatMessageModel $message)
    {
        if (!in_array($message->get('msg_type'), self::ValidMediaType)) {
            throw new Exception("消息类型不正确");
        }

        $sdkFileId = $message->get('raw_content')['sdkfileid'] ?? '';
        $md5 = $message->get('raw_content')['md5sum'] ?? "";
        if (empty($sdkFileId)) {
            throw new Exception("消息不完整, 缺少md5字段");
        }
        if (empty($md5)) {
            $md5 = md5($sdkFileId);
        }

        $request = [
            'corp_id' => $corp->get('id'),
            'chat_secret' => $corp->get('chat_secret'),
            'sdk_file_id' => $sdkFileId,
            'md5' => $md5,
        ];
        if ($message->get('msg_type') == 'file') {
            $request['origin_file_name'] = $message->get('raw_content')['filename'] ?? '';
        } elseif ($message->get('msg_type') == 'image') {
            $request['origin_file_name'] = Uuid::uuid4() . '.png';
        } elseif ($message->get('msg_type') == 'voice') {
            $request['origin_file_name'] = Uuid::uuid4() . '.amr';
        } elseif ($message->get('msg_type') == 'video') {
            $request['origin_file_name'] = Uuid::uuid4() . '.mp4';
        } elseif ($message->get('msg_type') == 'emotion') {
            $type = $message->get('raw_content')['type'] ?? 2;
            $request['origin_file_name'] = Uuid::uuid4() . ($type == 1 ? '.gif' : '.png');
        } elseif ($message->get('msg_type') == 'meeting_voice_call') {
            $request['origin_file_name'] = $message->get('raw_content')['voiceid'] . '.mp3';
        }
        $res = Yii::getRpcClient()->call('wxfinance.FetchMediaData', $request);
        if (!empty($res['url'])) {
            $message->update(['msg_content' => $res['url']]);
        }
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

        return Yii::getRpcClient()->call('wxfinance.FetchData', $request);
    }

    /**
     * 检查消息格式是否合法以及消息是否存在
     * @throws Throwable
     */
    private static function isValidMessage(array $msg): bool
    {
        if (empty($msg['decrypted_data']) || empty($msg['msgid']) || empty($msg['seq'])) {
            return false;
        }
        $decryptedData = json_decode($msg['decrypted_data'], true);
        if (empty($decryptedData['msgtime'])) {
            return false;
        }

        $message = ChatMessageModel::query()
            ->where(['and',
                ['msg_id' => $msg['msgid']],
                ['msg_time' => Carbon::createFromTimestampMsUTC($decryptedData['msgtime'])->timezone('Asia/Shanghai')->format('Y-m-d H:i:s.v')],
            ])
            ->getOne();

        return empty($message);
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
    private static function processMessage(array $msg): ChatMessageModel
    {
        $decryptedData = json_decode($msg['decrypted_data'], true);
        $msgType = $decryptedData['msgtype'] ?? '';
        $enumMsgType = EnumMessageType::from($msgType);
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
