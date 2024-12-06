<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\DB\BaseModel;
use Common\Yii;
use Exception;
use LogicException;
use Modules\Main\DTO\ChatSession\CollectDTO;
use Modules\Main\Enum\EnumChatCollectStatus;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserModel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;

class ChatSessionService
{
    /**
     *  按员工查询与客户的会话列表
     */
    public static function getCustomerConversationListByStaff(int $page, int $size, CorpModel $corp, string $staffUserId, array $search): array
    {
        // 拼接客户信息查询sql
        $customerWhere = "";
        if (!empty($search['keyword'])) {
            $customerWhere .= " and (c.external_name ilike '%{$search['keyword']}%' or c.external_userid = '{$search['keyword']}')";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (!empty($search["conversation_id"])) {
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
        $type = EnumChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, c.external_userid, c.external_name, c.avatar, c.staff_remark, c.corp_name
from main.chat_conversations as v
inner join main.customers as c on v."from" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}

union all

select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, c.external_userid, c.external_name, c.avatar, c.staff_remark, c.corp_name
from main.chat_conversations as v
inner join main.customers as c on v."to" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by last_msg_time desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        return BaseModel::buildPaginate($page, $size, $totalRes, $listRes);
    }

    /**
     * 获取收藏的单聊会话列表
     * @param int $page
     * @param int $size
     * @param CorpModel $corp
     * @param array $search
     * @return array
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function getCustomerConversationListByCollect(int $page, int $size, CorpModel $corp, array $search): array
    {
        // 拼接客户信息查询sql
        $customerWhere = "";
        if (!empty($search['keyword'])) {
            $customerWhere .= " and (c.external_name ilike '%{$search['keyword']}%' or c.external_userid = '{$search['keyword']}')";
        }

        //员工名称
        if (!empty($search['staff_name'])) {
            $customerWhere .= " and (s.name ilike '%{$search['staff_name']}%' or s.userid = '{$search['staff_name']}')";
        }

        $isCollect = EnumChatCollectStatus::Collect->value;
        // 存在会话ID搜索
        $whereSql = "";
        if (!empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            $whereSql = " and v.is_collect = '{$isCollect}' ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = EnumChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, c.external_userid, c.external_name, c.avatar, c.staff_remark, c.corp_name,s.name as staff_name,c.staff_userid
from main.chat_conversations as v
inner join main.staff as s on (v."from" = s.userid or v."to" = s.userid) and s.corp_id = '{$corp->get('id')}'
inner join main.customers as c on (v."from" = c.external_userid or v."to" = c.external_userid) and c.corp_id = '{$corp->get('id')}' and c.staff_userid=s.userid
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$customerWhere}
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by last_msg_time desc offset {$offset} limit {$size}";

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
        if (!empty($search['keyword'])) {
            $staffWhere .= " and s.name ilike '%{$search['keyword']}%'";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (!empty($search["conversation_id"])) {
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
        $type = EnumChatConversationType::Internal->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, s.userid, s.name, s.main_department
from main.chat_conversations as v
inner join main.staff as s on v."from" = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}

union all

select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, s.userid, s.name, main_department
from main.chat_conversations as v
inner join main.staff as s on v."to" = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}
SQL;
        $countSql = "select count(*) as total from ({$baseSql})";
        $listSql = "select * from ($baseSql) order by last_msg_time desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        // 获取部门id和名称的映射
        $departmentListIndex = [];
        $departmentIdList = array_unique(array_column($listRes, 'main_department'));
        if (!empty($departmentIdList)) {
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
        // 先查找这个员工所在的所有群聊
        $table = (new GroupModel)->getTableName();
        $subSql = "EXISTS (SELECT 1 FROM jsonb_array_elements({$table}.member_list) as members where members->>'userid' = '{$staffUserId}')";
        $query = GroupModel::query()->select(['chat_id', 'name', 'owner'])
            ->where(['corp_id' => $corp->get('id')])
            ->andWhere(new Expression($subSql));
        if (!empty($search['keyword'])) {
            $query->andWhere(['ilike', 'name', $search['keyword']]);
        }
        $groupList = $query->getAll();
        $groupChatIDMap = array_column($groupList->toArray(), null, 'chat_id');

        // 再从会话表中查找对应的会话记录
        $result = ChatConversationsModel::query()
            ->select(['id', 'last_msg_time', 'updated_at', 'to as chat_id','is_collect','collect_reason','collect_time'])
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['type' => EnumChatConversationType::Group->value],
                ['in', 'to', array_keys($groupChatIDMap)],
            ])
            ->orderBy('last_msg_time')
            ->paginate($page, $size);

        foreach ($result['items'] as $item) {
            $groupData = $groupChatIDMap[$item->get('chat_id')] ?? [];
            $item->append('name', $groupData['name'] ?? '');
            $item->append('owner', $groupData['owner'] ?? '');
            $item->append('is_collect', $item->get('is_collect')->value ?? 0);

        }

        return $result;
    }


    /**
     * @param int $page
     * @param int $size
     * @param CorpModel $corp
     * @param array $search
     * @return array
     * @throws Throwable
     */
    public static function getRoomConversationListByCollect(int $page, int $size, CorpModel $corp, array $search): array
    {
        // 拼接客户信息查询sql
        $customerWhere = "";
        if (!empty($search['keyword'])) {
            $customerWhere .= " and (c.name ilike '%{$search['keyword']}%')";
        }

        $isCollect = EnumChatCollectStatus::Collect->value;
        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        if (!empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            $toWhere = " and v.is_collect = '{$isCollect}' ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = EnumChatConversationType::Group->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, c.name, c.chat_id
from main.chat_conversations as v
inner join main.groups as c on v."to" = c.chat_id and c.corp_id = '{$corp->get('id')}'  {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by last_msg_time desc offset {$offset} limit {$size}";

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
        if (!empty($search['keyword'])) {
            $staffWhere .= " and s.name ilike '{$search['keyword']}'";
        }

        // 存在会话ID搜索
        $whereSql = "";
        $toWhere = "";
        $fromWhere = "";
        if (!empty($search["conversation_id"])) {
            $whereSql .= " and v.id = '" . $search["conversation_id"] . "'";
        } else {
            if (empty($externalUserid)) {
                throw new LogicException("缺少客户external_userid参数");
            }
            $toWhere = " and v.to = '{$externalUserid}' ";
            $fromWhere = " and v.from = '{$externalUserid}' ";

        }

        $offset = ($page - 1) * $size;
        $type = EnumChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, s.userid, s.name, s.main_department
from main.chat_conversations as v
inner join main.staff as s on v.from = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere} and s.corp_id = '{$corp->get('id')}' {$whereSql} {$toWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type}

union all

select v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, s.userid, s.name, s.main_department
from main.chat_conversations as v
inner join main.staff as s on v.to = s.userid and s.corp_id = '{$corp->get('id')}' {$staffWhere}  and s.corp_id = '{$corp->get('id')}' {$whereSql} {$fromWhere}
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
        if (!empty($departmentIdList)) {
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
        if ($conversation->get('from_role') == EnumChatMessageRole::Customer) {
            $userA = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('from')])
                ->getOne();
            if (empty($userA)) {
                $userA = [];
            } else {
                $userA = $userA->toArray();
            }

        } else {
            $userA = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('from')])
                ->getOne();
            $department = DepartmentModel::query()->where(['department_id' => $userA->get('main_department')])->getOne();
            $userA->append('department_name', $department->get('name'));
            if (empty($userA)) {
                $userA = [];
            } else {
                $userA = $userA->toArray();
            }
        }

        if ($conversation->get('to_role') == EnumChatMessageRole::Customer) {
            $userB = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('to')])
                ->getOne();
            if (empty($userB)) {
                $userB = [];
            } else {
                $userB = $userB->toArray();
            }
        } elseif ($conversation->get('to_role') == EnumChatMessageRole::Staff) {
            $userB = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('to')])
                ->getOne();
            $department = DepartmentModel::query()->where(['department_id' => $userB->get('main_department')])->getOne();
            $userB->append('department_name', $department->get('name'));
            if (empty($userB)) {
                $userB = [];
            } else {
                $userB = $userB->toArray();
            }
        }

        // 把相关员工、客户、群聊等信息拼接到消息列表中
        foreach ($result['items'] as $message) {
            /** @var ChatMessageModel $message */
            $message->append('from_detail',  ($message->get('from') == $conversation->get('from') ? $userA : $userB));
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
            if ($message->get('from_role') == EnumChatMessageRole::Staff) {
                $staffUseridList[] = $message->get('from');
            }
            if ($message->get('from_role') == EnumChatMessageRole::Customer) {
                $customerExternalUseridList[] = $message->get('from');
            }
        }


        // 提取出所有相关的员工信息
        $staffListMap = [];
        if (!empty($staffUseridList)) {
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
        if (!empty($customerExternalUseridList)) {
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
            if ($message->get('from_role') == EnumChatMessageRole::Customer) {
                $t = $customerListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            } elseif ($message->get('from_role') == EnumChatMessageRole::Staff) {
                $t = $staffListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            }
        }

        return $result;
    }


    /**
     * 加入收藏
     * @param CollectDTO $collectDTO
     * @return array
     * @throws Throwable
     */
    public static function joinCollect(CollectDTO $collectDTO): array
    {
        //取值
        $conversationId = $collectDTO->conversationId;
        $collectReason = $collectDTO->collectReason;

        // 获取会话信息
        $conversation = ChatConversationsModel::query()->where(['id' => $conversationId])->getOne();
        if (empty($conversation)) {
            throw new LogicException("会话id不正确");
        }

        //修改会话收藏状态 Exception
        $conversation->update([
            'is_collect' => EnumChatCollectStatus::Collect->value,
            'collect_reason' => $collectReason,
            'collect_time' => now(),
            'updated_at' => now(),
        ]);

        return ['conversation_id' => $conversationId,'is_collect' => EnumChatCollectStatus::Collect->value,'collect_reason' => $collectReason];
    }

    /**
     * 取消收藏
     * @param CollectDTO $collectDTO
     * @return array
     * @throws Throwable
     */
    public static function cancelCollect(CollectDTO $collectDTO): array
    {
        $conversationId = $collectDTO->conversationId;

        // 获取会话信息
        $conversation = ChatConversationsModel::query()->where(['id' => $conversationId])->getOne();
        if (empty($conversation)) {
            throw new LogicException("会话id不合法");
        }

        //修改会话收藏状态 Exception
        $conversation->update([
            'is_collect' => EnumChatCollectStatus::NoCollect->value,
            'updated_at' => now(),
        ]);

        return ['conversation_id' => $conversationId,'is_collect' => EnumChatCollectStatus::NoCollect->value];
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
        if (!empty($data["from"]) && !empty($data["from_type"])) {
            if ($data["from_type"] == EnumChatMessageRole::Customer->value) {//员工
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
        if (!empty($fromUserIds)) {
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

        if (!$res["items"]->isEmpty()) {
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
                if ($item->get('from_role') == EnumChatMessageRole::Customer) {
                    $cstUserIds[] = $item->get('from');
                } elseif ($item->get('from_role') == EnumChatMessageRole::Staff) {//发送人是员工
                    $staffUserIds[] = $item->get('from');
                }

                //接收人是客户
                if ($item->get('to_role') == EnumChatMessageRole::Customer) {
                    $cstUserIds[] = $item->get('to_list')[0];
                } elseif ($item->get('to_role') == EnumChatMessageRole::Staff) {//接收人是员工
                    $staffUserIds[] = $item->get('to_list')[0];
                }
            }

            //查询群信息
            if (!empty($roomIds)) {
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
            if (!empty($staffUserIds)) {
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
            if (!empty($cstUserIds)) {
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
                if (!empty($groupDetailIndex[$message->get('roomid')])) {
                    $group_name = $groupDetailIndex[$message->get('roomid')]['name'];
                }
                $message->append("group_info", [
                    "group_name" => $group_name,
                ]);
            }
            //追加发送人信息
            switch ($message->get('from_role')) {
                case EnumChatMessageRole::Customer:
                    $name = "";
                    $avatar = "";
                    if (!empty($customerInfoIndex[$message->get('from')])) {
                        $name = $customerInfoIndex[$message->get('from')]['external_name'];
                        $avatar = $customerInfoIndex[$message->get('from')]['avatar'];
                    }
                    $message->append("sender_info", [
                        "name" => $name,
                        "avatar" => $avatar,
                        "type" => $message->get('from_role'),
                    ]);

                    break;
                case EnumChatMessageRole::Staff:
                    $name = "";
                    if (!empty($staffUserInfoIndex[$message->get('from')])) {
                        $name = $staffUserInfoIndex[$message->get('from')]['name'];
                    }

                    $message->append("sender_info", [
                        "name" => $name,
                        "avatar" => "",
                        "type" => $message->get('from_role'),
                    ]);

                    break;

                case EnumChatMessageRole::Group:
                    $memberListIndex = ArrayHelper::index($groupDetailIndex[$message->get('roomid')]['member_list'], "userid");

                    $name = "";
                    if (!empty($memberListIndex[$message->get('from')])) {
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
                case EnumChatMessageRole::Customer:
                    $name = "";
                    $avatar = "";

                    if (!empty($customerInfoIndex[$message->get('to_list')[0]])) {
                        $name = $customerInfoIndex[$message->get('to_list')[0]]['external_name'];
                        $avatar = $customerInfoIndex[$message->get('to_list')[0]]['avatar'];
                    }
                    $message->append("receiver_info", [
                        "name" => $name,
                        "avatar" => $avatar,
                        "type" => $message->get('to_role'),
                    ]);

                    break;
                case EnumChatMessageRole::Staff:
                    $name = "";
                    if (!empty($staffUserInfoIndex[$message->get('to_list')[0]])) {
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
