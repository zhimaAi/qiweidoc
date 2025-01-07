<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Service;

use Carbon\Carbon;
use Common\Module;
use Common\Yii;
use Firebase\JWT\JWT;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Service\AuthService;
use Modules\TimeoutReplySingle\Enum\EnumInspectTimeType;
use Modules\TimeoutReplySingle\Enum\EnumTimeUnitType;
use Modules\TimeoutReplySingle\Model\ReplyRuleModel;
use Modules\TimeoutReplySingle\Model\RuleModel;
use Modules\TimeoutReplySingle\Model\TimeoutMessageModel;
use Throwable;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Security\Random;

class RuleService
{
    /**
     * @throws Throwable
     */
    public static function run(CorpModel $corp): void
    {
        $moduleInfo = Module::getLocalModuleConfig(Module::getCurrentModuleName());
        $moduleStartedAt = $moduleInfo['started_at'] ?? Carbon::today()->format('Y-m-d H:i:s.v');

        // 获取所有超时规则
        $rules = RuleModel::query()->where(['corp_id' => $corp->get('id')])->getAll();
        if ($rules->isEmpty()) {
            Yii::logger()->debug("未找到企业{$corp->get('id')}的超时规则");
            return;
        }

        // 获取回复规则
        /** @var ReplyRuleModel $replyRule */
        $replyRule = ReplyRuleModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->getOne();
        if (empty($replyRule)) {
            Yii::logger()->warning("未找到企业{$corp->get('id')}的回复规则");
            return;
        }

        foreach ($rules as $rule) {
            $subSql = "
                select 1
                from timeout_reply_single.timeout_messages
                where
                    corp_id = '{$corp->get('id')}' and
                    msg_id = msg.msg_id and
                    rule_id = {$rule->get('id')}
            ";
            $recentMessages = ChatMessageModel::query('msg')
                ->select(['msg_id', 'msg_time', 'msg.from', 'to_list', 'msg_time', 'msg_type', 'conversation_id', 'msg_content', 'staff_last_reply_time'])
                ->leftJoin('main.chat_conversations c', 'msg.conversation_id = c.id')
                ->where(['msg.corp_id' => $corp->get('id')])
                ->andWhere(['>=', 'msg_time', max($moduleStartedAt, $rule->get('created_at'), Carbon::now()->subDay()->toDateTimeString('m'))])
                ->andWhere(['conversation_type' => EnumChatConversationType::Single->value])
                ->andWhere(['msg.from_role' => EnumChatMessageRole::Customer->value])
                ->andWhere(new Expression("not exists ({$subSql})"))
                ->orderBy(['msg_time' => SORT_ASC])
                ->getAll();
            foreach ($recentMessages as $message) {
                $timeoutMinutes = (int)Carbon::parse($message->get('msg_time'))->diffInMinutes(Carbon::now());
                $ruleTimeoutMinute = EnumTimeUnitType::transferMinute($rule->get('timeout_unit'), $rule->get('timeout_value'));
                if ($timeoutMinutes < $ruleTimeoutMinute || $message->get('msg_time') < $message->get('staff_last_reply_time')) {
                    continue;
                }
                TimeoutMessageModel::create([
                    'corp_id'       => $corp->get('id'),
                    'msg_id'        => $message->get('msg_id'),
                    'rule_id'       => $rule->get('id'),
                ]);
                if (RuleService::hintMessage($message, $rule, $replyRule)) {
                    self::sendNotice($corp, $message, $rule, $timeoutMinutes);
                }
            }
        }
    }

    /**
     * 消息命中规则检测
     */
    public static function hintMessage(ChatMessageModel $message, RuleModel $rule, ReplyRuleModel $replyRule): bool
    {
        $external_userid = $message->get('from');
        $staff_userid =  $message->get('to_list')[0] ?? '';

        if (empty($external_userid) || empty($staff_userid)) {
            Yii::logger()->warning("消息不完整，未找到员工和客户信息", ['message' => $message->toArray()]);
            return false;
        }

        if (!$rule->get('enabled')) {
            return false;
        }

        if (in_array($message->get('msg_type'), [EnumMessageType::MeetingVoiceCall->value, EnumMessageType::VoipText->value])) {
            Yii::logger()->debug('消息类型' . $message->get('msg_type') . '过滤掉', ['message' => $message->toArray()]);
            return false;
        }
        if ($replyRule->get('include_image_msg') && $message->get('msg_type') == EnumMessageType::Image->value) {
            Yii::logger()->debug("图片消息过滤掉", ['message' => $message->toArray()]);
            return false;
        }
        if ($replyRule->get('include_emoticons_msg') && $message->get('msg_type') == EnumMessageType::Emotion->value) {
            Yii::logger()->debug("emotion消息过滤掉", ['message' => $message->toArray()]);
            return false;
        }
        if ($replyRule->get('include_emoji_msg') && $message->get('msg_type') == EnumMessageType::Text->value && isWeChatEmoji($message->get('msg_content'))) {
            Yii::logger()->debug('emoji消息过滤掉', ['message' => $message->toArray()]);
            return false;
        }

        // 全匹配关键词过滤
        foreach ($replyRule->get('filter_full_match_word_list') as $word) {
            if ($message->get('msg_content') == $word) {
                Yii::logger()->debug('全匹配关键词过滤了', ['message' => $message->toArray()]);
                return false;
            }
        }

        // 半匹配关键词过滤
        foreach ($replyRule->get('filter_half_match_word_list') as $word) {
            if (str_contains($message->get('msg_content'), $word)) {
                Yii::logger()->debug('半匹配关键词过滤了', ['message' => $message->toArray()]);
                return false;
            }
        }

        // 不在质检员工列表的过滤掉
        if (! in_array($staff_userid, $rule->get('staff_userid_list'))) {
            Yii::logger()->debug('未匹配到质检员工', ['message' => $message->toArray()]);
            return false;
        }

        // 如果是自定义时间段且不在该时间段内过滤掉
        if ($rule->get('inspect_time_type') == EnumInspectTimeType::Custom) {
            if (! ReplyRuleModel::withinTheTimePeriod($rule->get('custom_time_list'), Carbon::now())) {
                Yii::logger()->debug('不在员工自定义时间段检查范围内', ['message' => $message->toArray()]);
                return false;
            }
            if (! ReplyRuleModel::withinTheTimePeriod($rule->get('custom_time_list'), Carbon::parse($message->get('msg_time')))) {
                Yii::logger()->debug('消息不在自定义时间段检查范围内', ['message' => $message->toArray()]);
                return false;
            }
        }

        // 如果是工作时间且不在该时间段内的过滤掉
        if ($rule->get('inspect_time_type') == EnumInspectTimeType::WorkerDay) {
            if (! ReplyRuleModel::withinTheTimePeriod($replyRule->get('working_hours'), Carbon::now())) {
                Yii::logger()->debug('不在员工工作时间段检查范围内', ['message' => $message->toArray()]);
                return false;
            }
            if (! ReplyRuleModel::withinTheTimePeriod($replyRule->get('working_hours'), Carbon::parse($message->get('msg_time')))) {
                Yii::logger()->debug('消息不在工作时间段检查范围内', ['message' => $message->toArray()]);
                return false;
            }
        }

        return true;
    }

    /**
     * 发送通知
     */
    public static function sendNotice(CorpModel $corp, ChatMessageModel $message, RuleModel $rule, int $timeoutMinutes)
    {
        // 提醒员工本人
        if ($rule->get('is_remind_staff_himself')) {
            self::sendQiWeiMessage($corp, $message->get('to_list')[0], $message, $timeoutMinutes);
        }
        // 指定员工提醒
        if ($rule->get('is_remind_staff_designation')) {
            foreach ($rule->get('designate_remind_userid_list') as $userid) {
                self::sendQiWeiMessage($corp, $userid, $message, $timeoutMinutes);
            }
        }
    }

    /**
     * 生成jwt token
     * */
    public static function generateAuthToken(CorpModel $corp, string $conversationId, string $staffUserid, string $externalUserid)
    {
        $payload = [
            'staff_userid' => $staffUserid,
            'external_userid' => $externalUserid,
            'conversation_id' => $conversationId,
            'corp_id' => $corp->get('id'),
        ];
        return JWT::encode($payload, 'staff_qw_login_jwt_key', 'HS256');
    }

    /**
     * 发送企微应用消息
     */
    public static function sendQiWeiMessage(CorpModel $corp, string $toUserid, ChatMessageModel $message, int $timeoutMinutes)
    {
        $cst = CustomersModel::query()->where(['external_userid' => $message->get('from')])->getOne();
        $staff = StaffModel::query()->where(['userid' => $message->get('to_list')[0]])->getOne();
        $msgTime = Carbon::parse($message->get('msg_time'))->format('Y-m-d H:i:s');

        $token = self::generateAuthToken($corp, $message->get('conversation_id'), $toUserid, $message->get('from'));

        // 员工本人
        if ($toUserid == $staff->get('userid')) {
            $replyText = "去回复";
            $targetUrl = AuthService::getLoginDomain() . "/#/tools/h5/openChat?token={$token}";
        } else { // 非员工本人
            $replyText = "去查看";
            $moduleName = Module::getCurrentModuleName();
            $targetUrl = AuthService::getLoginDomain() . "/modules/{$moduleName}/#/module/timeout-reply-single/h5/session-message?token={$token}";
        }

        $enumType = EnumMessageType::from($message->get('msg_type'));
        if ($enumType == EnumMessageType::Text) {
            $content = $message->get('msg_content');
        } else {
            $content = $enumType->getLabel();
        }

        $content = <<<MD
### 单聊超时提醒
客户：{$cst->get('external_name')}
员工: {$staff->get('name')}
消息发送时间: {$msgTime}
消息内容: {$content}
超时时间: {$timeoutMinutes}分钟

[{$replyText}]({$targetUrl})
MD;
        Yii::logger()->debug($content);
        $corp->postWechatApi('/cgi-bin/message/send', [
            'touser'    => $toUserid,
            'msgtype'   => 'markdown',
            'agentid'   => $corp->get('agent_id'),
            'markdown'  => ['content' => $content],
        ], 'json');
    }
}
