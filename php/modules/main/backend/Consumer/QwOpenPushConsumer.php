<?php

namespace Modules\Main\Consumer;

use Common\Job\Producer;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use EasyWeChat\Work\Message;
use Exception;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\CustomerTagModel;
use Modules\Main\Model\GroupModel;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

readonly class QwOpenPushConsumer
{
    private CorpModel $corp;
    private Message $message;

    public function __construct(CorpModel $corp, Message $message)
    {
        $this->corp = $corp;
        $this->message = $message;
    }

    /**
     * @throws ServerExceptionInterface
     * @throws BadResponseException
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws Throwable
     * @throws TransportExceptionInterface
     */
    public function handle(): void
    {
        // 验证消息
        if ($this->message->ToUserName != $this->corp->get('id')) {
            throw new Exception("企业id不匹配");
        }

        // 成员关注事件
        $this->handleSubscribeEvent();

        // 成员取消关注事件
        $this->handleUnSubscribeEvent();

        // 通讯录变更事件
        $this->handleChangeContactEvent();

        // 外部联系人变更事件
        $this->handleChangeExternalContactEvent();

        // 客户群变更事件
        $this->handleChangeExternalChatEvent();

        // 客户标签变更事件
        $this->handleChangeExternalTagEvent();

        // 会话存档回调事件
        $this->handleMsgAuditNotifyEvent();
    }

    /**
     * 成员关注事件
     *
     * @throws Throwable
     */
    private function handleSubscribeEvent(): void
    {
        if ($this->message->Event == 'subscribe') {
            Producer::dispatch(SyncDepartmentConsumer::class, ['corp' => $this->corp]);
        }
    }

    /**
     * 成员取消关注事件
     *
     * @throws Throwable
     */
    private function handleUnSubscribeEvent(): void
    {
        if ($this->message->Event == 'unsubscribe') {
            Producer::dispatch(SyncDepartmentConsumer::class, ['corp' => $this->corp]);
        }
    }

    /**
     * 通讯录变更事件
     *
     * @throws Throwable
     */
    private function handleChangeContactEvent(): void
    {
        if ($this->message->Event == 'change_contact') {
            Producer::dispatch(SyncDepartmentConsumer::class, ['corp' => $this->corp]);
        }
    }

    /**
     * 外部联系人变更事件
     *
     * @throws Throwable
     */
    private function handleChangeExternalContactEvent(): void
    {
        if ($this->message->Event == 'change_external_contact') {
            if (!$this->message->has('UserID') || !$this->message->has('ExternalUserID')) {
                throw new Exception('外部联系人变更事件中缺少UserID或ExternalUerID字段');
            }

            // 添加/编辑企业客户事件
            if (in_array($this->message->ChangeType, [
                'add_external_contact',
                'edit_external_contact',
                'add_half_external_contact',
            ])) {
                CustomersModel::syncOne(
                    $this->corp,
                    $this->message->offsetGet('UserID'),
                    $this->message->offsetGet('ExternalUserID')
                );
            }

            // 删除企业客户事件
            if ($this->message->ChangeType == 'del_external_contact') {
                CustomersModel::query()->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['staff_userid' => $this->message->UserID],
                    ['external_userid' => $this->message->ExternalUserID],
                ])->update(['add_status' => 0]);
            }
        }
    }

    /**
     * 客户群变更事件
     *
     * @throws Throwable
     */
    private function handleChangeExternalChatEvent(): void
    {
        if ($this->message->Event == 'change_external_chat') {
            if (!$this->message->has('ChatId')) {
                throw new Exception('客户群变更事件缺少ChatId字段');
            }

            // 客户群新增/更新事件
            if (in_array($this->message->ChangeType, ['create', 'update'])) {
                GroupModel::syncOne($this->corp, $this->message->offsetGet('ChatId'));
            }

            // 客户群解散事件
            if ($this->message->ChangeType == 'dismiss') {
                GroupModel::query()->where(['and',
                    ["corp_id" => $this->corp->get('id')],
                    ["chat_id" => $this->message->ChatId],
                ])->deleteAll();
            }
        }
    }

    /**
     * 客户标签变更事件
     *
     * @throws Throwable
     */
    private function handleChangeExternalTagEvent(): void
    {
        if ($this->message->Event == 'change_external_tag') {
            if (!$this->message->has('Id') || !$this->message->has('TagType')) {
                throw new Exception("客户标签变更事件中缺少Id字段或TagType字段");
            }

            $res = $this->corp->postWechatApi('/cgi-bin/externalcontact/get_corp_tag_list');
            if (empty($res['tag_group'])) {
                throw new Exception("请求企微获取标签库失败");
            }

            // 标签删除事件
            if ($this->message->ChangeType == 'delete') {
                CustomerTagModel::query()->where(['and',
                    ['corp_id' => $this->corp->get('id')],
                    ['tag_id' => $this->message->Id],
                ])->deleteAll();
            }
        }
    }

    /**
     * 会话存档回调事件
     *
     * @throws Throwable
     */
    private function handleMsgAuditNotifyEvent(): void
    {
        if ($this->message->Event == 'msgaudit_notify') {
            // Producer::dispatch(SyncSessionMessageConsumer::class, ['corp' => $this->corp]);
        }
    }
}
