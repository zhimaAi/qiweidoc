<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 会话存档消息拉取服务
 */

namespace App\Services;

use App\ChatConversationType;
use App\ChatMessageRole;
use App\Consumers\DownloadChatSessionMediasConsumer;
use App\Libraries\Core\Yii;
use App\Models\ChatConversationsModel;
use App\Models\ChatMessageModel;
use App\Models\CorpModel;
use App\Models\CustomersModel;
use App\Models\GroupModel;
use App\Models\StaffModel;
use Exception;
use Ramsey\Uuid\Uuid;
use Throwable;

class ChatSessionPullService
{
    private const MESSAGE_LIMIT = 1000;
    private static array $messageTypeHandlers;
    private static CorpModel $corp;
    const ValidMediaType = [
        'image',
        'voice',
        'video',
        'emotion',
        'file',
    ];

    /**
     * 拉取并保存会话消息
     *
     * @throws Throwable
     */
    public static function handleMessage(CorpModel $corp): void
    {
        self::$corp = $corp;
        self::initMessageTypeHandlers();

        // 拉取消息
        $messages = self::fetchMessages();
        $lastSeq = null;
        foreach ($messages as $msg) {

            if (! empty($msg['seq'])) {
                $lastSeq = $msg['seq'];
            }

            // 过滤掉不能识别的消息
            if (! self::isValidMessage($msg)) {
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

            // 异步下载资源
            if (in_array($messageData->get('msg_type'), self::ValidMediaType)) {
                DownloadChatSessionMediasConsumer::dispatch(['corp' => $corp, 'message' => $messageData]);
            }
        }

        // 更新消息序号
        if (! empty($lastSeq)) {
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
        if (! in_array($message->get('msg_type'), self::ValidMediaType)) {
            throw new Exception("消息类型不正确");
        }

        $sdkFileId = $message->get('raw_content')['sdkfileid'] ?? '';
        $md5 = $message->get('raw_content')['md5sum'] ?? "";
        if (empty($md5) || empty($sdkFileId)) {
            throw new Exception("消息不完整，缺少md5或sdkfileid字段");
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
        }
        $res = Yii::getRpcClient()->call('wxfinance.FetchMediaData', $request);
        if (empty($res['url'])) {
            throw new Exception("下载文件出错，url为空");
        }

        $message->update(['msg_content' => $res['url']]);
    }

    private static function initMessageTypeHandlers(): void
    {
        self::$messageTypeHandlers = [
            'text' => fn ($data) => ['raw_content' => $data['text'], 'msg_content' => $data['text']['content']],
            'image' => fn ($data) => ['raw_content' => $data['image']],
            'revoke' => fn ($data) => ['raw_content' => $data['revoke']],
            'disagree' => fn ($data) => ['raw_content' => $data['agree']],
            'voice' => fn ($data) => ['raw_content' => $data['voice']],
            'video' => fn ($data) => ['raw_content' => $data['video']],
            'card' => fn ($data) => ['raw_content' => $data['card']],
            'location' => fn ($data) => ['raw_content' => $data['location']],
            'emotion' => fn ($data) => ['raw_content' => $data['emotion']],
            'file' => fn ($data) => ['raw_content' => $data['file']],
            'link' => fn ($data) => ['raw_content' => $data['link']],
            'weapp' => fn ($data) => ['raw_content' => $data['weapp']],
            'chatrecord' => fn ($data) => ['raw_content' => $data['chatrecord']],
            'todo' => fn ($data) => ['raw_content' => $data['todo']],
            'vote' => fn ($data) => ['raw_content' => $data['vote']],
            'collect' => fn ($data) => ['raw_content' => $data['collect']],
            'redpacket' => fn ($data) => ['raw_content' => $data['redpacket']],
            'meeting' => fn ($data) => ['raw_content' => $data['meeting']],
            'meeting_notification' => fn ($data) => ['raw_content' => $data['info']],
            'docmsg' => fn ($data) => ['raw_content' => $data['doc']],
            'markdown' => fn ($data) => ['raw_content' => $data['info']],
            'news' => fn ($data) => ['raw_content' => $data['news']],
            'calendar' => fn ($data) => ['raw_content' => $data['calendar']],
            'mixed' => fn ($data) => ['raw_content' => $data['mixed']],
            'meeting_voice_call' => fn ($data) => ['raw_content' => array_merge($data['meeting'], ['voiceid' => $data['voiceid']])],
            'voip_doc_share' => fn ($data) => ['raw_content' => array_merge($data['voip_doc_share'], ['voipid' => $data['voipid']])],
            'external_redpacket' => fn ($data) => ['raw_content' => $data['redpacket']],
            'sphfeed' => fn ($data) => ['raw_content' => $data['sphfeed']],
            'voiptext' => fn ($data) => ['raw_content' => $data['info']],
        ];
    }

    /**
     * 从企微拉取消息
     * 由golang处理并自动解密
     */
    private static function fetchMessages(): array
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
                ['msg_time' => date('Y-m-d H:i:s', $decryptedData['msgtime'] / 1000)],
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
        if (! empty($messageData->get('roomid'))) {
            $type = ChatConversationType::Group;
            $idList[] = $messageData->get('roomid');
        } else {
            if (self::checkIsExternal($messageData->get('from'), $messageData->get('to_list')[0])) {
                $type = ChatConversationType::Single;
            } else {
                $type = ChatConversationType::Internal;
            }
            $idList[] = $messageData->get('from');
            $idList[] = $messageData->get('to_list')[0];
        }
        sort($idList);
        $id = md5(implode('', $idList));

        $conversation = ChatConversationsModel::query()->where(['id' => $id])->getOne();
        if (empty($conversation)) {
            if (self::hasExternalPrefix($messageData->get('from'))) {
                $fromRole = ChatMessageRole::Customer;
                CustomersModel::hasConversationSave(self::$corp, $messageData->get('from'));
            } else {
                $fromRole = ChatMessageRole::Staff;
                StaffModel::hasConversationSave(self::$corp, $messageData->get('from'));
            }

            if ($type == ChatConversationType::Group) {
                $toRole = ChatMessageRole::Group;
                GroupModel::hasConversationSave(self::$corp, $messageData->get('roomid'));
            } else {
                if (self::hasExternalPrefix($messageData->get('to_list')[0])) {
                    $toRole = ChatMessageRole::Customer;
                    CustomersModel::hasConversationSave(self::$corp, $messageData->get('to_list')[0]);
                } else {
                    $toRole = ChatMessageRole::Staff;
                    StaffModel::hasConversationSave(self::$corp, $messageData->get('to_list')[0]);
                }
            }

            $conversation = ChatConversationsModel::create([
                'id' => $id,
                'corp_id' => self::$corp->get('id'),
                'type' => $type,
                'from' => $messageData->get('from'),
                'from_role' => $fromRole,
                'to' => $type == ChatConversationType::Group ? $messageData->get('roomid') : $messageData->get('to_list')[0],
                'to_role' => $toRole,
                'last_msg_time' => $messageData->get('msg_time'),
            ]);
        } else {
            $conversation->update(['last_msg_time' => $messageData->get('msg_time')]);
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
        $content = [];

        $decryptedData = json_decode($msg['decrypted_data'], true);
        $msgType = $decryptedData['msgtype'] ?? '';
        if (isset(self::$messageTypeHandlers[$msgType])) {
            $content = (self::$messageTypeHandlers[$msgType])($decryptedData);
        }

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
            'msg_time' => date('Y-m-d H:i:s', $decryptedData['msgtime'] / 1000),
        ], $content));
        if (self::hasExternalPrefix($messageData->get('from'))) {
            $messageData->set('from_role', ChatMessageRole::Customer);
        } else {
            $messageData->set('from_role', ChatMessageRole::Staff);
        }

        if (! empty($messageData->get('roomid'))) {
            $messageData->set('to_role', ChatMessageRole::Group);
        } else {
            if (self::hasExternalPrefix($messageData->get('to_list')[0])) {
                $messageData->set('to_role', ChatMessageRole::Customer);
            } else {
                $messageData->set('to_role', ChatMessageRole::Staff);
            }
        }
        $messageData->save();

        return $messageData;
    }

    public static function hasExternalPrefix($id)
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
