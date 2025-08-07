<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\DB\BaseModel;
use Common\Yii;
use Exception;
use LogicException;
use Modules\Main\DTO\ChatSession\CollectDTO;
use Modules\Main\Enum\EnumChatCollectStatus;
use Modules\Main\Enum\EnumChatConversationType;
use Modules\Main\Enum\EnumChatMessageRole;
use Modules\Main\Enum\EnumMessageType;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\CustomerTagModel;
use Modules\Main\Model\DepartmentModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\StorageModel;
use Modules\Main\Model\UserModel;
use Modules\Main\Model\UserReadChatDetailModel;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\Expression;

class ChatSessionService
{
    //可以筛选的消息类型，文本，语音，文件，图片，音视频通话
    const FilterMsgType = [
        "text" => "text",
        "voice" => "voice",
        "file" => "file",
        "image" => "image",
        "video" => "video",
        "voiptext" => ["voiptext", "meeting_voice_call"],

    ];

    //需要下载的文件类型
    const ValidMediaType = [
        'image',
        'voice',
        'video',
        'emotion',
        'file',
        'meeting_voice_call',
        "video",
    ];

    /**
     *  按员工查询与客户的会话列表
     */
    public static function getCustomerConversationListByStaff(int $page, int $size, CorpModel $corp,UserModel $user, string $staffUserId, array $search): array
    {

        $arrayCorpInfo = $corp->toArray();
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

        //存在标签筛选，通过标签找一下客户
        if (!empty($search["tag_ids"])) {
            //先找符合标签条件的客户ID列表
            $cstIds = CustomerTagModel::query()->select(["tag_id","rb_iterate(external_userid) as external_userid"])->where(["corp_id"=>$corp->get("id")])->andWhere(["in","tag_id",$search["tag_ids"]])->getAll()->toArray();
            $external_userids = [];
            if (!empty($cstIds)) {
                $external_userids = array_values(array_unique(array_column($cstIds,"external_userid")));
            }
            if (!empty($external_userids)) {
                $allCstList = CustomersModel::query()->select(["external_userid"])->where(["corp_id"=>$corp->get("id")])->andWhere(["in","id",$external_userids])->getAll()->toArray();
                $whereSql .= " and c.external_userid in ('".implode("','",array_column($allCstList,"external_userid"))."')";
            } else {
                $whereSql .= " and c.external_userid = '_' ";
            }
        }

        $fields = " v.id, v.last_msg_time, v.updated_at,v.type,v.is_collect,v.collect_reason,v.collect_time, c.external_name, c.avatar, c.staff_remark, c.corp_name ";

        //查询客户标签列表
        if (!empty($arrayCorpInfo["show_customer_tag"])) {
            $fields .= ",c.staff_tag_id_list ";
        }

        // 拼接基础sql
        $offset = ($page - 1) * $size;
        $type = EnumChatConversationType::Single->value;
        $baseSql = /** @lang sql */ <<<SQL
select {$fields}, v."from" as external_userid
from main.chat_conversations as v
left join main.customers as c on v."from" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$toWhere}

union all

select {$fields}, v."to" as external_userid
from main.chat_conversations as v
left join main.customers as c on v."to" = c.external_userid and c.corp_id = '{$corp->get('id')}' and c.staff_userid = '{$staffUserId}' {$customerWhere}
where v.corp_id = '{$corp->get('id')}'
  and v.type = {$type} {$whereSql} {$fromWhere}
SQL;
        $countSql = "select count(*) as total from({$baseSql})";
        $listSql = "select * from ({$baseSql}) order by last_msg_time desc offset {$offset} limit {$size}";

        $totalRes = Yii::db()->createCommand($countSql)->queryColumn()[0];
        $listRes = Yii::db()->createCommand($listSql)->queryAll();

        //拿到客户之后，需要获取配置检查一下是否需要展示标签和已读标识
        if (!empty($arrayCorpInfo["show_is_read"])) {
            //查询已读标记
            $readDetailList = UserReadChatDetailModel::query()->where(["users_id"=>$user->get("id")])->andWhere(["in","conversation_id",array_column($listRes,"id")])->getAll()->toArray();
            $readDetailListIndex = ArrayHelper::index($readDetailList,"conversation_id");

            foreach ($listRes as &$item) {
                if (array_key_exists($item["id"] ?? 0, $readDetailListIndex)) {
                    $item["is_read"] = 1;
                } else {
                    $item["is_read"] = 0;
                }
            }
        }

        //展示标签
        if (!empty($arrayCorpInfo["show_customer_tag"]) ) {
            $allTagGroups = TagsService::customer($corp);

            $allTags = [];
            foreach ($allTagGroups as $allTagGroup) {
                foreach ($allTagGroup["tag"] as &$item) {
                    $item["group_id"] = $allTagGroup["group_id"];
                }
                $allTags = array_merge($allTags,$allTagGroup["tag"]??[]);
            }
            $allTagIndex = ArrayHelper::index($allTags,"id");

            foreach ($listRes as &$item) {

                $preTag = [];
                $lastTag = [];


                $staff_tag_id_list = json_decode($item["staff_tag_id_list"] ?: '[]',true);
                foreach ($staff_tag_id_list as $tag_id) {
                    $tagInfo = $allTagIndex[$tag_id]??[];
                    if (empty($tagInfo)) {
                        continue;
                    }

                    if (in_array($tagInfo["group_id"],$arrayCorpInfo["show_customer_tag_config"]["group_ids"])){
                        $preTag[] = $tagInfo;
                    } else if (in_array($tagInfo["id"],$arrayCorpInfo["show_customer_tag_config"]["tag_ids"])){
                        $lastTag[] = $tagInfo;
                    }

                }
                $item["tag_data"] = array_merge($preTag,$lastTag);
            }
        }

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
select
    v.id,
    v.last_msg_time,
    v.updated_at,
    v.type,
    v.is_collect,
    v.collect_reason,
    v.collect_time,
    case when v.from_role = 2 then v.to else v."from" end as external_userid,
    c.external_name,
    c.avatar,
    c.staff_remark,
    c.corp_name,
    s.name as staff_name,
    c.staff_userid
from main.chat_conversations as v
inner join main.staff as s on (v."from" = s.userid or v."to" = s.userid) and s.corp_id = '{$corp->get('id')}'
left join main.customers as c on (v."from" = c.external_userid or v."to" = c.external_userid) and c.corp_id = '{$corp->get('id')}' and c.staff_userid=s.userid
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
            ->orderBy(['last_msg_time' => SORT_DESC])
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
     * 获取聊天内容（不包括群聊）
     * @throws Throwable
     */
    public static function getMessageListByConversation(int $page, int $size, CorpModel $corp, string $conversationId,array $params = []): array
    {
        if (empty($conversationId)) {
            throw new LogicException("会话id不能为空");
        }

        // 获取会话信息
        $conversation = ChatConversationsModel::query()->where(['id' => $conversationId])->getOne();
        if (empty($conversation)) {
            throw new LogicException("会话id不合法");
        }

        $query = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['conversation_id' => $conversation->get('id')],
            ]);

        //存在消息类型过滤
        if (!empty($params["msg_type"]) && in_array($params["msg_type"], array_keys(self::FilterMsgType))) {
            if ($params["msg_type"] == EnumMessageType::VoipText->value) {
                $query->andWhere(["in", "msg_type", self::FilterMsgType[$params["msg_type"] ?? ""] ?? ["_"]]);
            } else {
                $query->andWhere(["msg_type" => self::FilterMsgType[$params["msg_type"] ?? ""] ?? "_"]);
            }
        }

        //存在消息内容关键字搜索
        if (!empty($params["msg_content"])){
            $query->andWhere(["ilike","msg_content",$params["msg_content"]]);
        }

        //存在消息时间范围
        if (!empty($params["msg_start_time"]) && !empty($params["msg_end_time"])){
            $query->andWhere(["between","msg_time",$params["msg_start_time"],$params["msg_end_time"]]);
        }

        // 分页获取聊天消息
        $result = $query->orderBy(['msg_time' => SORT_DESC])->paginate($page, $size);
        if ($result['items']->isEmpty()) {
            return $result;
        }

        if ($conversation->get('from_role') == EnumChatMessageRole::Customer) {
            $fromDetail = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('from')])
                ->getOne()?->toArray();
        } else {
            $staff = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('from')])
                ->getOne();
            if (empty($staff)) {
                $fromDetail = [];
            } else {
                $department = DepartmentModel::query()->where(['department_id' => $staff->get('main_department')])->getOne();
                $staff->append('department_name', $department->get('name'));
                $fromDetail = $staff->toArray();
            }
        }

        if ($conversation->get('to_role') == EnumChatMessageRole::Customer) {
            $toDetail = CustomersModel::query()
                ->select(['id', 'avatar', 'gender', 'external_userid', 'external_name', 'staff_remark', 'corp_name'])
                ->where(['external_userid' => $conversation->get('to')])
                ->getOne()?->toArray();
        } else {
            $staff = StaffModel::query()
                ->select(['id', 'name', 'status', 'enable', 'alias', 'main_department'])
                ->where(['userid' => $conversation->get('to')])
                ->getOne();
            if (empty($staff)) {
                $toDetail = [];
            } else {
                $department = DepartmentModel::query()->where(['department_id' => $staff->get('main_department')])->getOne();
                $staff->append('department_name', $department->get('name'));
                $toDetail = $staff->toArray();
            }
        }

        // 把相关员工、客户、群聊、文件下载链接等信息拼接到消息列表中
        foreach ($result['items'] as $message) {
            /** @var ChatMessageModel $message */
            $message->set("msg_time", date("Y-m-d H:i:s", strtotime($message->get("msg_time"))));

            $message->append('from_detail', ($message->get('from') == $conversation->get('from') ? $fromDetail : $toDetail));
            $message->append('to_detail',  ($message->get('from') == $conversation->get('from') ? $toDetail : $fromDetail));

            // 动态获取下载链接
            if (in_array($message->get('msg_type'), self::ValidMediaType) && is_md5($message->get('msg_content'))) {
                $message->append('msg_content', StorageService::getDownloadUrl($message->get('msg_content')));
            }
        }

        //如果开启已读标记
        if (!empty($corp->get("show_is_read")) && !empty($params['users_id'])) {
            $filter = ["corp_id"=>$corp->get("id"),"users_id"=>$params["users_id"],"conversation_id"=>$conversationId];
            UserReadChatDetailModel::firstOrCreate($filter,$filter);
        }

        $result['from_detail'] = $fromDetail;
        $result['to_detail'] = $toDetail;
        $result['from_role'] = $conversation->get('from_role');
        $result['to_role'] = $conversation->get('to_role');

        return $result;
    }

    /**
     * 根据群聊获取聊天内容
     * @throws Throwable
     */
    public static function getMessageListByGroup(int $page, int $size, CorpModel $corp, string $groupChatId, array $params = []): array
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

        $query = ChatMessageModel::query()
            ->where(['and',
                ['corp_id' => $corp->get('id')],
                ['roomid' => $groupChatId]
            ]);

        //存在消息类型过滤
        if (!empty($params["msg_type"]) && in_array($params["msg_type"], array_keys(self::FilterMsgType))) {
            if ($params["msg_type"] == EnumMessageType::VoipText->value) {
                $query->andWhere(["in", "msg_type", self::FilterMsgType[$params["msg_type"] ?? ""] ?? ["_"]]);
            } else {
                $query->andWhere(["msg_type" => self::FilterMsgType[$params["msg_type"] ?? ""] ?? "_"]);
            }
        }

        //存在消息内容关键字搜索
        if (!empty($params["msg_content"])){
            $query->andWhere(["ilike","msg_content",$params["msg_content"]]);
        }

        //存在消息时间范围
        if (!empty($params["msg_start_time"]) && !empty($params["msg_end_time"])){
            $query->andWhere(["between","msg_time",$params["msg_start_time"],$params["msg_end_time"]]);
        }

        // 根据群聊查找所有相关的聊天记录
        $result = $query->orderBy(['msg_time' => SORT_DESC])
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
        // 把消息发送者、文件下载链接等信息拼接到消息列表中
        foreach ($result['items'] as $message) {
            $message->set("msg_time", date("Y-m-d H:i:s", strtotime($message->get("msg_time"))));
            /** @var ChatMessageModel $message */
            if ($message->get('from_role') == EnumChatMessageRole::Customer) {
                $t = $customerListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            } elseif ($message->get('from_role') == EnumChatMessageRole::Staff) {
                $t = $staffListMap[$message->get('from')] ?? [];
                $message->append('from_detail', $t);
            }

            // 动态获取下载链接
            if (in_array($message->get('msg_type'), self::ValidMediaType) && is_md5($message->get('msg_content'))) {
                $message->append('msg_content', StorageService::getDownloadUrl($message->get('msg_content')));
            }
        }

        $result['group'] = $group;

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

        if (isset($data["init"]) && $data["init"] ) {
            return [];
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

        Yii::db()->createCommand("SET pg_bigm.similarity_limit TO 0.1")->execute();
        $query = ChatMessageModel::query()
            ->where($where)
            ->addSelect(new Expression("*,bigm_similarity(msg_content, '{$data['keyword']}') as similarity"))
            ->andWhere(['=%', 'msg_content', $data["keyword"] ?? ""])
            ->andWhere(['>', 'msg_time', $start_time])
            ->andWhere(['<', 'msg_time', $stop_time]);

        //存在发送人
        if (!empty($fromUserIds)) {
            $query->andWhere(['in', 'from', array_values(array_unique($fromUserIds))]);
        }

        $res = $query->orderBy(['similarity' => SORT_DESC])
            ->paginate($data['page'] ?? 1, $data['size'] ?? 10);

        if ($res["items"]->isEmpty()) {
            return $res;
        }
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

    /**
     * @param CorpModel $corp
     * Notes: 获取会话存档展示配置
     * User: rand
     * Date: 2024/12/10 16:50
     * @return array
     * @throws Throwable
     */
    public static function getChatConfigInfo(CorpModel $corp)
    {
        return CorpModel::query()->select(["show_customer_tag", "show_customer_tag_config", "show_is_read"])->where(["id" => $corp->get("id")])->getOne()->toArray();
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 保存会话存档展示配置
     * User: rand
     * Date: 2024/12/10 16:54
     * @return int
     * @throws Throwable
     */
    public static function saveChatConfig(CorpModel $corp, $data)
    {
        return CorpModel::query()->where([
            "id" => $corp->get("id")
        ])->update([
            "show_customer_tag" => $data["show_customer_tag"] ?? 0,
            "show_customer_tag_config" => $data["show_customer_tag_config"] ?? [],
            "show_is_read" => $data["show_is_read"] ?? 1,
        ]);
    }
}
