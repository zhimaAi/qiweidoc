<?php

namespace Modules\TimeoutReplyGroup\Controller;

use Common\Controller\BaseController;
use LogicException;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Model\StaffModel;
use Modules\TimeoutReplyGroup\DTO\RuleDTO;
use Modules\TimeoutReplyGroup\Model\RuleModel;
use Modules\TimeoutReplyGroup\Service\RuleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class RuleController extends BaseController
{
    /**
     * 获取规则列表
     */
    public function list(ServerRequestInterface $request) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        RuleService::run($corp);

        // 分页参数
        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $list = RuleModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);

        // 获取群聊列表
        $staffTable = (new StaffModel())->getTableName();
        $t = array_column($list['items']->toArray(), 'group_chat_id_list');
        $allChatIdList = [];
        foreach ($t as $v) {
            $allChatIdList = array_merge($allChatIdList, $v);
        }
        $allChatIdList = array_values(array_unique($allChatIdList));
        $allGroupChatList = GroupModel::query('g')
            ->leftJoin($staffTable . ' as s', 'g.owner = s.userid')
            ->select('g.chat_id,g.name,s.name as owner_name')
            ->where(['in', 'chat_id', $allChatIdList])
            ->getAll()
            ->toArray();
        $allGroupChatList = ArrayHelper::index($allGroupChatList, 'chat_id');

        // 获取群聊员工列表
        $t = array_column($list['items']->toArray(), 'group_staff_userid_list');
        $allGroupStaffUserIdList = [];
        foreach ($t as $v) {
            $allGroupStaffUserIdList = array_merge($allGroupStaffUserIdList, $v);
        }
        $allGroupStaffUserIdList = array_values(array_unique($allGroupStaffUserIdList));
        $allGroupStaffUserList = StaffModel::query()->select('userid,name')->where(['in', 'userid', $allGroupStaffUserIdList])->getAll()->toArray();
        $allGroupStaffUserList = ArrayHelper::index($allGroupStaffUserList, 'userid');

        // 获取提醒员工列表
        $allStaffUserIdList = [];
        foreach ($list['items']->toArray() as $item) {
            foreach ($item['remind_rules'] as $remindRule) {
                $allStaffUserIdList = array_merge($allStaffUserIdList, $remindRule['designate_remind_userid_list']);
            }
        }
        $allStaffUserIdList = array_values(array_unique($allStaffUserIdList));
        $allRemindStaffUserList = StaffModel::query()->select('userid,name')->where(['in', 'userid', $allStaffUserIdList])->getAll()->toArray();
        $allRemindStaffUserList = ArrayHelper::index($allRemindStaffUserList, 'userid');

        $data = $list['items']->toArray();
        foreach ($data as &$item) {
            // 拼接群聊信息
            $groupList = [];
            foreach ($item['group_chat_id_list'] as $groupChatId) {
                $groupList[] = $allGroupChatList[$groupChatId] ?? '';
            }
            $groupList = array_values(array_filter($groupList));
            $item['group_chat_id_detail_list'] = $groupList;

            // 拼接群聊员工详细信息
            $staffUserList = [];
            foreach ($item['group_staff_userid_list'] as $staffUserid) {
                $staffUserList[] = $allGroupStaffUserList[$staffUserid] ?? '';
            }
            $staffUserList = array_values(array_filter($staffUserList));
            $item['group_staff_user_list'] = $staffUserList;

            // 拼接提醒员工详细信息
            foreach ($item['remind_rules'] as &$remindRule) {
                $remindRule['designate_remind_user_list'] = [];
                foreach ($remindRule['designate_remind_userid_list'] as $userid) {
                    $remindRule['designate_remind_user_list'][] = $allRemindStaffUserList[$userid] ?? [];
                }
            }
        }

        $list['items'] = $data;
        return $this->jsonResponse($list);
    }

    /**
     * 显示规则详情
     */
    public function show(ServerRequestInterface $request, #[RouteArgument('id')] int $id) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        // 获取规则详情
        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        // 拼接群聊详情
        $staffTable = (new StaffModel())->getTableName();
        $groupList = GroupModel::query('g')
            ->leftJoin($staffTable . ' as s', 'g.owner = s.userid')
            ->select('g.chat_id,g.name,s.name as owner_name')
            ->where(['in', 'chat_id', $rule->get('group_chat_id_list')])
            ->getAll();
        $rule->append('group_chat_detail_list', $groupList->toArray());

        // 拼接群聊员工详细信息
        $staffUserList = StaffModel::query()
            ->select('userid,name')
            ->where(['in', 'userid', $rule->get('group_staff_userid_list')])
            ->getAll();
        $rule->append('group_staff_user_list', $staffUserList->toArray());

        // 拼接提醒员工详细信息
        $remindRules = $rule->get('remind_rules');
        foreach ($remindRules as &$remindRule) {
            $remindRule['remind_staff_user_list'] = StaffModel::query()
                ->select('userid,name')
                ->where(['in', 'userid', $remindRule['designate_remind_userid_list']])
                ->getAll()
                ->toArray();
        }
        $rule->append('remind_rules', $remindRules);

        return $this->jsonResponse($rule);
    }

    /**
     * 添加规则
     */
    public function save(ServerRequestInterface $request) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $dto = new RuleDTO($request->getParsedBody());
        $data = $dto->toArray();
        foreach ($data['remind_rules'] as &$remindRule) {
            $remindRule['updated_at'] = now(true);
        }
        RuleModel::create(array_merge(['corp_id' => $corp->get('id')], $data));

        return $this->jsonResponse();
    }

    /**
     * 更新规则
     */
    public function update(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        $dto = new RuleDTO($request->getParsedBody());
        $data = $dto->toArray();
        foreach ($data['remind_rules'] as &$remindRule) {
            $remindRule['updated_at'] = now(true);
        }

        $rule->update($data);

        return $this->jsonResponse();
    }

    /**
     * 启用规则
     */
    public function enable(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        $rule->update(['enabled' => true]);

        return $this->jsonResponse();
    }

    /**
     * 禁用规则
     */
    public function disable(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        $rule->update(['enabled' => false]);

        return $this->jsonResponse();
    }

    /**
     * 删除规则
     */
    public function destroy(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        $rule->delete();

        return $this->jsonResponse();
    }
}
