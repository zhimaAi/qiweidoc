<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Services;

use App\ChatConversationType;
use App\ChatMessageRole;
use App\Libraries\Core\DB\BaseModel;
use App\Libraries\Core\Yii;
use App\Models\ChatConversationsModel;
use App\Models\ChatMessageModel;
use App\Models\CorpModel;
use App\Models\CustomersModel;
use App\Models\DepartmentModel;
use App\Models\GroupModel;
use App\Models\StaffModel;
use App\Models\UserModel;
use LogicException;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;

class ChatSessionService
{
    /**
     *  按员工查询与客户的会话列表
     */
    public static function getCustomerConversationListByStaff(int $page, int $size, CorpModel $corp, string $staffUserId, array $search): array
    {
        // 拼接客户信息查询sql
        $customerWhere = "";
        if (! empty($search['keyword'])) {
            $customerWhere .= " and (c.external_name ilike '%{$search['keyword']}%' or c.external_userid = '{$search['keyword']}')";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (! empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            if (empty($staffUserId)) {
                throw new LogicException("缺少员工id参数");
            }
            $toWhere = " and v.to = '{$staffUserId}' ";
            $fromWhere = " and v.from = '{$staffUserId}' ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = ChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at, c.external_userid, c.external_name, c.avatar, c.staff_remark, c.corp_name
from chat_conversations as v
inner join customers as c on v."from" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}

union all

select v.id, v.last_msg_time, v.updated_at, c.external_userid, c.external_name, c.avatar, c.staff_remark, c.corp_name
from chat_conversations as v
inner join customers as c on v."to" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by updated_at desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);
    }

    /**
     * 按员工查询与员工的会话列表
     * @throws Throwable
     */
    public static function getStaffConversationListByStaff(int $page, int $size, CorpModel $corp, string $staffUserId, array $search): array
    {
        // 拼接员工名称搜索sql
        $staffWhere = "";
        if (! empty($search['keyword'])) {
            $staffWhere .= " and s.name ilike '%{$search['keyword']}%'";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (! empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            if (empty($staffUserId)) {
                throw new LogicException("缺少员工id参数");
            }
            $toWhere = " and v.to = '{$staffUserId}' ";
            $fromWhere = " and v.from = '{$staffUserId}' ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = ChatConversationType::Internal->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at, s.userid, s.name, s.main_department
from chat_conversations as v
inner join staff as s on v."from" = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}

union all

select v.id, v.last_msg_time, v.updated_at, s.userid, s.name, main_department
from chat_conversations as v
inner join staff as s on v."to" = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}
SQL;
        $countSql = "select count(*) as total from ({$baseSql})";
        $listSql = "select * from ($baseSql) order by updated_at desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        // 获取部门id和名称的映射
        $departmentListIndex = [];
        $departmentIdList = array_unique(array_column($listRes, 'main_department'));
        if (! empty($departmentIdList)) {
            $departmentList = DepartmentModel::query()
                ->select(['department_id', 'name', 'corp_id'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'department_id', $departmentIdList],
                ])
                ->getAll();
            $departmentListIndex = ArrayHelper::index($departmentList->toArray(), 'department_id');
        }

        // 把部门名称附加到结果集中
        array_walk($listRes, function (&$item) use ($departmentListIndex) {
            $item['department_name'] = $departmentListIndex[$item['main_department']]['name'];
        });

        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);
    }

    /**
     * 按员工查询与群的会话列表
     * @throws Throwable
     */
    public static function getRoomConversationListByStaff(int $page, int $size, CorpModel $corp, string $staffUserId, array $search): array
    {
        // 拼接群聊查询参数
        $groupWhere = "";
        if (! empty($search['keyword'])) {
            $groupWhere .= " and g.name ilike '%{$search['keyword']}%'";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (! empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            if (empty($staffUserId)) {
                throw new LogicException("缺少员工id参数");
            }
            $toWhere = " and v.to = '{$staffUserId}' ";
            $fromWhere = " and v.from = '{$staffUserId}' ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = ChatConversationType::Group->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at, g.chat_id, g.name, g.owner
from chat_conversations as v
inner join "groups" as g on v."to" = g.chat_id and g.corp_id = '{$corp->get('id')}' {$groupWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}

union all

select v.id, v.last_msg_time, v.updated_at, g.chat_id, g.name, g.owner
from chat_conversations as v
inner join "groups" as g on v."from" = g.chat_id and g.corp_id = '{$corp->get('id')}' {$groupWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}
SQL;
        $countSql = "select count(*) as total from ({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by updated_at offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);
    }

    /**
     * 按客户查询与员工的会话列表
     * @throws Throwable
     */
    public static function getStaffConversationListByCustomer(int $page, int $size, CorpModel $corp, string $externalUserid, array $search): array
    {
        // 拼接员工名称搜索sql
        $staffWhere = "";
        if (! empty($search['keyword'])) {
            $staffWhere .= " and s.name ilike '{$search['keyword']}'";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (! empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            if (empty($externalUserid)) {
                throw new LogicException("缺少客户external_userid参数");
            }
            $toWhere = " and v.to = '{$externalUserid}' ";
            $fromWhere = " and v.from = '{$externalUserid}' ";

        }

        $offset = ($page - 1) * $size;
        $type = ChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at, s.userid, s.name, s.main_department
from chat_conversations as v
inner join staff as s on v.from = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere} and s.corp_id = '{$corp->get('id')}' {$whereSql} {$toWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type}

union all

select v.id, v.last_msg_time, v.updated_at, s.userid, s.name, s.main_department
from chat_conversations as v
inner join staff as s on v.to = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}  and s.corp_id = '{$corp->get('id')}' {$whereSql} {$fromWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type}
SQL;
        $countSql = "select count(*) as total from ({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by updated_at desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        // 获取部门id和名称的映射
        $departmentListIndex = [];
        $departmentIdList = array_unique(array_column($listRes, 'main_department'));
        if (! empty($departmentIdList)) {
            $departmentList = DepartmentModel::query()
                ->select(['department_id', 'name', 'corp_id'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'department_id', $departmentIdList],
                ])
                ->getAll();
            $departmentListIndex = ArrayHelper::index($departmentList->toArray(), 'department_id');
        }

        // 把部门名称附加到结果集中
        array_walk($listRes, function (&$item) use ($departmentListIndex) {
            $item['department_name'] = $departmentListIndex[$item['main_department']]['name'];
        });

        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);
    }

    /**
     * 获取聊天内容
     * @throws Throwable
     */
    public static function getMessageListByConversation(int $page, int $size, CorpModel $corp, string $conversationId): array
    {
        if (empty($conversationId)) {
            throw new LogicException("会话id不能为空");
        }

        // 获取会话信息
        $conversation = ChatConversationsModel::query()->where(['id' => $conversationId])->getOne();
        if (empty($conversation)) {
            throw new LogicException("会话id不合法");
        }

        // 分页获取聊天消息
        $result = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['conversation_id' => $conversation->get('id')],
            ])
            ->orderBy(['seq' => SORT_DESC])
            ->paginate($page, $size);
        if ($result['items']->isEmpty()) {
            return $result;
        }

        $userA = $userB = [];
        if ($conversation->get('from_role') == ChatMessageRole::Customer) {
            $userA = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('from')])
                ->getOne();
        } else {
            $userA = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('from')])
                ->getOne();
            $department = DepartmentModel::query()->where(['department_id' => $userA->get('main_department')])->getOne();
            $userA->append('department_name', $department->get('name'));
        }

        if ($conversation->get('to_role') == ChatMessageRole::Customer) {
            $userB = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('to')])
                ->getOne();
        } elseif ($conversation->get('to_role') == ChatMessageRole::Staff) {
            $userB = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('to')])
                ->getOne();
            $department = DepartmentModel::query()->where(['department_id' => $userB->get('main_department')])->getOne();
            $userB->append('department_name', $department->get('name'));
        }

        // 把相关员工、客户、群聊等信息拼接到消息列表中
        foreach ($result['items'] as $message) {
            /** @var ChatMessageModel $message */
            $message->append('from_detail',  ($message->get('from') == $conversation->get('from') ? $userA->toArray() : $userB->toArray()));
        }

        return $result;
    }

    /**
     * 根据群聊获取聊天内容
     * @throws Throwable
     */
    public static function getMessageListByGroup(int $page, int $size, CorpModel $corp, string $groupChatId): array
    {
        // 判断群聊id
        if (empty($groupChatId)) {
            throw new LogicException("群聊id不能为空");
        }

        // 判断群聊是否存在
        $group = GroupModel::query()->where(['and', ['corp_id' => $corp->get('id')], ['chat_id' => $groupChatId]])->getOne();
        if (empty($group)) {
            throw new LogicException('群聊不存在');
        }

        // 根据群聊查找所有相关的聊天记录
        $result = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['roomid' => $groupChatId],
            ])
            ->orderBy(['seq' => SORT_DESC])
            ->paginate($page, $size);

        // 根据聊天记录提取出所有发送者的id集合
        $staffUseridList = [];
        $customerExternalUseridList = [];
        foreach ($result['items'] as $message) {
            /** @var ChatMessageModel $message */
            if ($message->get('from_role') == ChatMessageRole::Staff) {
                $staffUseridList[] = $message->get('from');
            }
            if ($message->get('from_role') == ChatMessageRole::Customer) {
                $customerExternalUseridList[] = $message->get('from');
            }
        }


        // 提取出所有相关的员工信息
        $staffListMap = [];
        if (! empty($staffUseridList)) {
            // 获取员工列表
            $staffList = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'corp_id', 'userid', 'main_department'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'userid', $staffUseridList],
                ])
                ->getAll();

            // 获取部门id和名称的映射
            $departmentIdList = array_unique(array_column($staffList->toArray(), 'main_department'));
            $departmentList = DepartmentModel::query()
                ->select(['department_id', 'name', 'corp_id'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'department_id', $departmentIdList],
                ])
                ->getAll();
            $departmentListIndex = ArrayHelper::index($departmentList->toArray(), 'department_id');

            // 把部门名称附加到员工列表集中
            foreach ($staffList as $staff) {
                /** @var StaffModel $staff */
                $staff->append('department_name', $departmentListIndex[$staff->get('main_department')]['name']);
            }

            // 员工列表map
            $staffListMap = array_column($staffList->toArray(), null, 'userid');
        }

        // 提取出所有相关的客户信息
        $customerListMap = [];
        if (! empty($customerExternalUseridList)) {
            $customerList = CustomersModel::query()
                ->select(['id', 'avatar', 'corp_id', 'gender', 'external_userid', 'external_name'])
                ->where(['and',
                    ['corp_id' => $corp->get('id')],
                    ['in', 'external_userid', $customerExternalUseridList],
                ])
                ->getAll();
            $customerListMap = array_column($customerList->toArray(), null, 'external_userid');
        }

        // 把消息发送者信息拼接到结果集中
        foreach ($result['items'] as $message) {
            /** @var ChatMessageModel $message */
            if ($message->get('from_role') == ChatMessageRole::Customer) {
                $t = $customerListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            } elseif ($message->get('from_role') == ChatMessageRole::Staff) {
                $t = $staffListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            }
        }

        return $result;
    }

    /**
     * @param UserModel $currentUser
     * @param $data
     * Notes: 会话搜索
     * User: rand
     * Date: 2024/11/11 12:06
     * @return array
     * @throws Throwable
     */
    public static function getSearch(UserModel $currentUser, $data): array
    {
        $start_time = $data["start_time"] ?? "";
        $stop_time = $data["stop_time"] ?? date("Y-m-d H:i:s", time());
        if (empty($start_time)) {
            $start_time = date("Y-m-d H:i:s", strtotime("-180 days"));
        }

        if (empty($data["keyword"])) {
            throw new LogicException('请输入查找内容');
        }

        $where = ['and',
            ["corp_id" => $currentUser->get('corp_id')],
            ["msg_type" => "text"],
        ];

        //会话类型
        if (isset($data["session_type"])) {
            $where[] = ['conversation_type' => $data["session_type"]];
        }

        //发送人
        $fromUserIds = [];
        if (! empty($data["from"]) && ! empty($data["from_type"])) {
            if ($data["from_type"] == ChatMessageRole::Customer->value) {//员工
                $customerUserList = CustomersModel::query()
                    ->where(['and',
                        ['corp_id' => $currentUser->get('corp_id')],
                        ['ilike', 'external_name', $data['from']],
                    ])
                    ->getAll();
                $fromUserIds = array_column($customerUserList->toArray(), "external_userid");
            } else {
                $staffUserList = StaffModel::query()
                    ->where(['and',
                        ['corp_id' => $currentUser->get('corp_id')],
                        ['ilike', 'name', $data["from"]],
                    ])
                    ->getAll();
                $fromUserIds = array_column($staffUserList->toArray(), "userid");
            }
        }

        $query = ChatMessageModel::query()
            ->where($where)
            ->andWhere(['ilike', 'msg_content', $data["keyword"]])
            ->andWhere(['>', 'msg_time', $start_time])
            ->andWhere(['<', 'msg_time', $stop_time]);

        //存在发送人
        if (! empty($fromUserIds)) {
            $query->andWhere(['in', 'from', array_values(array_unique($fromUserIds))]);
        }

        $res = $query->orderBy(['msg_time' => SORT_DESC])
            ->paginate($data['page'] ?? 1, $data['size'] ?? 10);

        //查询客户信息
        $customerInfoIndex = [];
        //查询员工信息
        $staffUserInfoIndex = [];
        //查询群信息
        $groupDetailIndex = [];

        if (! $res["items"]->isEmpty()) {
            $roomIds = [];

            $staffUserIds = [];
            $cstUserIds = [];

            foreach ($res['items'] as $item) {
                /** @var ChatMessageModel $item */

                //群聊
                if ($item->get('roomid') != "") {
                    $roomIds[] = $item->get('roomid');
                }

                //发送人是客户
                if ($item->get('from_role') == ChatMessageRole::Customer) {
                    $cstUserIds[] = $item->get('from');
                } elseif ($item->get('from_role') == ChatMessageRole::Staff) {//发送人是员工
                    $staffUserIds[] = $item->get('from');
                }

                //接收人是客户
                if ($item->get('to_role') == ChatMessageRole::Customer) {
                    $cstUserIds[] = $item->get('to_list')[0];
                } elseif ($item->get('to_role') == ChatMessageRole::Staff) {//接收人是员工
                    $staffUserIds[] = $item->get('to_list')[0];
                }
            }

            //查询群信息
            if (! empty($roomIds)) {
                $groupDetail = GroupModel::query()
                    ->select(["chat_id", "name", "member_list"])
                    ->where(['and',
                        ['corp_id' => $currentUser->get('corp_id')],
                        ['in', 'chat_id', $roomIds],
                    ])
                    ->getAll();
                $groupDetailIndex = ArrayHelper::index($groupDetail->toArray(), "chat_id");
            }

            //查询员工信息
            if (! empty($staffUserIds)) {
                $staffUserInfo = StaffModel::query()
                    ->select(["name", "userid"])
                    ->where(['and',
                        ['corp_id' => $currentUser->get('corp_id')],
                        ['in', 'userid', array_values(array_unique($staffUserIds))],
                    ])
                    ->getAll();
                $staffUserInfoIndex = ArrayHelper::index($staffUserInfo->toArray(), "userid");
            }

            //查询客户信息
            if (! empty($cstUserIds)) {
                $customerInfo = CustomersModel::query()
                    ->select(["external_name", "external_userid", "avatar"])
                    ->where(['and',
                        ['corp_id' => $currentUser->get('corp_id')],
                        ['in', 'external_userid', array_values(array_unique($cstUserIds))],
                    ])
                    ->getAll();
                $customerInfoIndex = ArrayHelper::index($customerInfo->toArray(), "external_userid");
            }
        }

        foreach ($res['items'] as $message) {
            /** @var ChatMessageModel $message */

            //群聊，追加群信息
            if ($message->get('roomid') != "") {
                $group_name = "已删除群聊";
                if (! empty($groupDetailIndex[$message->get('roomid')])) {
                    $group_name = $groupDetailIndex[$message->get('roomid')]['name'];
                }
                $message->append("group_info", [
                    "group_name" => $group_name,
                ]);
            }
            //追加发送人信息
            switch ($message->get('from_role')) {
                case ChatMessageRole::Customer:
                    $name = "";
                    $avatar = "";
                    if (! empty($customerInfoIndex[$message->get('from')])) {
                        $name = $customerInfoIndex[$message->get('from')]['external_name'];
                        $avatar = $customerInfoIndex[$message->get('from')]['avatar'];
                    }
                    $message->append("sender_info", [
                        "name" => $name,
                        "avatar" => $avatar,
                        "type" => $message->get('from_role'),
                    ]);

                    break;
                case ChatMessageRole::Staff:
                    $name = "";
                    if (! empty($staffUserInfoIndex[$message->get('from')])) {
                        $name = $staffUserInfoIndex[$message->get('from')]['name'];
                    }

                    $message->append("sender_info", [
                        "name" => $name,
                        "avatar" => "",
                        "type" => $message->get('from_role'),
                    ]);

                    break;

                case ChatMessageRole::Group:
                    $memberListIndex = ArrayHelper::index($groupDetailIndex[$message->get('roomid')]['member_list'], "userid");

                    $name = "";
                    if (! empty($memberListIndex[$message->get('from')])) {
                        $name = $memberListIndex[$message->get('from')]["name"];
                    }

                    $message->append("sender_info", [
                        "name" => $name,
                        "avatar" => "",
                        "type" => $message->get('from_role'),
                    ]);

                    break;
            }


            //追加接收人信息
            switch ($message->get('to_role')) {
                case ChatMessageRole::Customer:
                    $name = "";
                    $avatar = "";

                    if (! empty($customerInfoIndex[$message->get('to_list')[0]])) {
                        $name = $customerInfoIndex[$message->get('to_list')[0]]['external_name'];
                        $avatar = $customerInfoIndex[$message->get('to_list')[0]]['avatar'];
                    }
                    $message->append("receiver_info", [
                        "name" => $name,
                        "avatar" => $avatar,
                        "type" => $message->get('to_role'),
                    ]);

                    break;
                case ChatMessageRole::Staff:
                    $name = "";
                    if (! empty($staffUserInfoIndex[$message->get('to_list')[0]])) {
                        $name = $staffUserInfoIndex[$message->get('to_list')[0]]['name'];
                    }
                    $message->append("receiver_info", [
                        "name" => $name,
                        "avatar" => "",
                        "type" => $message->get('to_role'),
                    ]);

                    break;
            }
        }

        return $res;
    }
}
