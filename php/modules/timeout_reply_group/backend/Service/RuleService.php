<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplyGroup\Service;

use Carbon\Carbon;
use Common\Module;
use Common\Yii;
use Firebase\JWT\JWT;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Library\Middlewares\WxAuthMiddleware;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Service\AuthService;
use Modules\TimeoutReplyGroup\Enum\EnumGroupType;
use Modules\TimeoutReplyGroup\Enum\EnumInspectTimeType;
use Modules\TimeoutReplyGroup\Enum\EnumTimeUnitType;
use Modules\TimeoutReplyGroup\Model\ReplyRuleModel;
use Modules\TimeoutReplyGroup\Model\RuleModel;
use Modules\TimeoutReplyGroup\Model\TimeoutMessageModel;
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
            foreach ($rule->get('remind_rules') as $index => $remindRule) {
                if (! isset($remindRule['timeout_unit']) ||
                    ! isset($remindRule['timeout_value']) ||
                    ! isset($remindRule['is_remind_group_member']) ||
                    ! isset($remindRule['is_remind_staff_designation']) ||
                    ! isset($remindRule['designate_remind_userid_list']) ||
                    ! isset($remindRule['updated_at'])
                ) {
                    Yii::logger()->error("提醒规则字段缺失", ['rule' => $rule]);
                    continue;
                }
                $subSql = "
                    select 1
                    from timeout_reply_group.timeout_messages
                    where
                        corp_id = '{$corp->get('id')}' and
                        msg_id = msg.msg_id and
                        rule_id = {$rule->get('id')} and
                        remind_rule_index = $index
                ";
                $recentMessages = ChatMessageModel::query('msg')
                    ->select(['msg_id', 'msg_time', 'msg.from', 'roomid', 'msg_time', 'msg_type', 'conversation_id', 'msg_content', 'staff_last_reply_time'])
                    ->leftJoin('main.chat_conversations c', 'msg.conversation_id = c.id')
                    ->where(['msg.corp_id' => $corp->get('id')])
                    ->andWhere(['>=', 'msg_time', max($moduleStartedAt, $remindRule['updated_at'], Carbon::now()->subDay()->toDateTimeString('m'))])
                    ->andWhere(['conversation_type' => EnumChatConversationType::Group->value])
                    ->andWhere(['msg.from_role' => EnumChatMessageRole::Customer->value])
                    ->andWhere(new Expression("not exists ({$subSql})"))
                    ->orderBy(['msg_time' => SORT_ASC])
                    ->getAll();
                foreach ($recentMessages as $message) {
                    $timeoutMinutes = (int)Carbon::parse($message->get('msg_time'))->diffInMinutes(Carbon::now());
                    $ruleTimeoutMinute = EnumTimeUnitType::transferMinute(EnumTimeUnitType::from($remindRule['timeout_unit']), $remindRule['timeout_value']);
                    if ($timeoutMinutes < $ruleTimeoutMinute || $message->get('msg_time') < $message->get('staff_last_reply_time')) {
                        continue;
                    }
                    /** @var GroupModel $group */
                    $group = GroupModel::query()
                        ->where([
                            'corp_id' => $corp->get('id'),
                            'chat_id' => $message->get('roomid')
                        ])
                        ->getOne();
                    if (empty($group)) {
                        Yii::logger()->debug('未找到群聊', ['message' => $message->toArray()]);
                        continue;
                    }
                    TimeoutMessageModel::create([
                        'corp_id'               => $corp->get('id'),
                        'msg_id'                => $message->get('msg_id'),
                        'rule_id'               => $rule->get('id'),
                        'remind_rule_index'     => $index,
                    ]);
                    if (RuleService::hintMessage($message, $group, $rule, $replyRule)) {
                        self::sendNotice($corp, $message, $group, $rule, $remindRule, $timeoutMinutes);
                    }
                }
            }
        }
    }

    /**
     * 消息命中规则检测
     */
    public static function hintMessage(ChatMessageModel $message, GroupModel $group, RuleModel $rule, ReplyRuleModel $replyRule): bool
    {
        if (!$rule->get('enabled')) {
            Yii::logger()->debug('规则未开启', ['rule' => $rule->toArray()]);
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

        // 指定群聊过滤
        if ($rule->get('group_type') == EnumGroupType::ChatIdList && ! in_array($message->get('roomid'), $rule->get('group_chat_id_list'))) {
            Yii::logger()->debug('未匹配到群聊', ['message' => $message->toArray()]);
            return false;
        }

        // 指定群聊员工过滤
        if ($rule->get('group_type') == EnumGroupType::StaffIdList) {
            $memberUseridList = array_column($group->get('member_list'), 'userid');
            if (! array_intersect($rule->get('group_staff_userid_list'), $memberUseridList)) {
                Yii::logger()->debug('指定员工未匹配到群聊', ['message' => $message->toArray()]);
                return false;
            }
        }

        // 群名关键词检测
        if ($rule->get('group_type') == EnumGroupType::KeywordList) {
            $includeKeywordList = $rule->get('group_keyword_list')['include_list'] ?? [];
            $excludeKeywordList = $rule->get('group_keyword_list')['exclude_list'] ?? [];

            foreach ($excludeKeywordList as $keyword) {
                if (str_contains($group->get('name'), $keyword)) {
                    Yii::logger()->debug("排除群名关键词", ['message' => $message->toArray()]);
                    return false;
                }
            }
            $filter = true;
            foreach ($includeKeywordList as $keyword) {
                if (str_contains($group->get('name'), $keyword)) {
                    $filter = false;
                }
            }
            if ($filter) {
                Yii::logger()->debug("不包含关键词", ['message' => $message->toArray()]);
                return false;
            }
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
    public static function sendNotice(CorpModel $corp, ChatMessageModel $message, GroupModel $group, RuleModel $rule, array $remindRule, int $timeoutMinutes)
    {
        $hasRemindUseridList = [];

        // 提醒群主
        if ($rule->get('is_remind_group_owner')) {
            self::sendQiWeiMessage($corp, $group->get('owner'), $message, $timeoutMinutes, true, $group->get('name'));
            $hasRemindUseridList[] = $group->get('owner');
        }

        // 提醒群内员工
        if (!empty($remindRule['is_remind_group_member'])) {
            foreach ($group->get('member_list') as $member) {
                if (!empty($member['type']) && $member['type'] == 1 && !empty($member['userid']) && !in_array($member['userid'], $hasRemindUseridList)) {
                    self::sendQiWeiMessage($corp, $member['userid'], $message, $timeoutMinutes, true, $group->get('name'));
                    $hasRemindUseridList[] = $member['userid'];
                }
            }
        }

        // 提醒指定员工
        if (!empty($remindRule['is_remind_staff_designation'])) {
            $memberUseridList = array_column($group->get('member_list'), 'userid');
            foreach ($remindRule['designate_remind_userid_list'] as $userid) {
                if (!in_array($userid, $hasRemindUseridList)) {
                    self::sendQiWeiMessage($corp, $userid, $message, $timeoutMinutes, in_array($userid, $memberUseridList), $group->get('name'));
                    $hasRemindUseridList[] = $userid;
                }
            }
        }
    }

    /**
     * 生成jwt token
     * */
    public static function generateAuthToken(CorpModel $corp, string $groupChatId, string $staffUserid, string $externalUserid)
    {
        $payload = [
            'staff_userid' => $staffUserid,
            'external_userid' => $externalUserid,
            'group_chat_id' => $groupChatId,
            'corp_id' => $corp->get('id'),
        ];
        return JWT::encode($payload, 'staff_qw_login_jwt_key', 'HS256');
    }

    /**
     * 发送企微应用消息
     */
    public static function sendQiWeiMessage(CorpModel $corp, string $toUserid, ChatMessageModel $message, int $timeoutMinutes, bool $inGroup, string $groupName)
    {
        $cst = CustomersModel::query()->where(['external_userid' => $message->get('from')])->getOne();
        $msgTime = Carbon::parse($message->get('msg_time'))->format('Y-m-d H:i:s');

        $token = self::generateAuthToken($corp, $message->get('roomid'), $toUserid, $message->get('from'));

        // 在群内
        if ($inGroup) {
            $replyText = "去回复";
            $targetUrl = AuthService::getLoginDomain() . "/#/tools/h5/openChat?token={$token}";
        } else { // 不在群内
            $replyText = "去查看";
            $moduleName = Module::getCurrentModuleName();
            $targetUrl = AuthService::getLoginDomain() . "/modules/{$moduleName}/#/module/timeout-reply-group/h5/session-message?token={$token}";
        }

        if (($enumType = EnumMessageType::from($message->get('msg_type'))) == EnumMessageType::Text) {
            $content = $message->get('msg_content');
        } else {
            $content = $enumType->getLabel();
        }

        $content = <<<MD
### 群聊超时提醒
客户：{$cst->get('external_name')}
群聊名称: $groupName
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
