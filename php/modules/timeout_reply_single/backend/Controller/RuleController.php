<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Controller;

use Common\Controller\BaseController;
use LogicException;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Modules\TimeoutReplySingle\DTO\RuleDTO;
use Modules\TimeoutReplySingle\Model\RuleModel;
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

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $list = RuleModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);

        // 获取质检员工列表
        $t = array_column($list['items']->toArray(), 'staff_userid_list');
        $allStaffUserIdList = [];
        foreach ($t as $v) {
            $allStaffUserIdList = array_merge($allStaffUserIdList, $v);
        }
        $allStaffUserIdList = array_values(array_unique($allStaffUserIdList));
        $allStaffUserList = StaffModel::query()->select('userid,name')->where(['in', 'userid', $allStaffUserIdList])->getAll()->toArray();
        $allStaffUserList = ArrayHelper::index($allStaffUserList, 'userid');

        // 获取提醒员工列表
        $t = array_column($list['items']->toArray(), 'designate_remind_userid_list');
        $allStaffUserIdList = [];
        foreach ($t as $v) {
            $allStaffUserIdList = array_merge($allStaffUserIdList, $v);
        }
        $allStaffUserIdList = array_values(array_unique($allStaffUserIdList));
        $allRemindStaffUserList = StaffModel::query()->select('userid,name')->where(['in', 'userid', $allStaffUserIdList])->getAll()->toArray();
        $allRemindStaffUserList = ArrayHelper::index($allRemindStaffUserList, 'userid');

        foreach ($list['items'] as $item) {
            // 拼接质检员工详细信息
            $staffUserList = [];
            foreach ($item->get('staff_userid_list') as $staffUserid) {
                $staffUserList[] = $allStaffUserList[$staffUserid] ?? '';
            }
            $staffUserList = array_values(array_filter($staffUserList));
            $item->append('staff_user_list', $staffUserList);

            // 拼接提醒员工详细信息
            $remindStaffUserlist = [];
            if ($item->get('is_remind_staff_designation')) {
                foreach ($item->get('designate_remind_userid_list') as $staffUserid) {
                    $remindStaffUserlist[] = $allRemindStaffUserList[$staffUserid] ?? '';
                }
                $remindStaffUserlist = array_values(array_filter($remindStaffUserlist));
            }
            $item->append('remind_staff_user_list', $remindStaffUserlist);
        }

        return $this->jsonResponse($list);
    }

    public function show(ServerRequestInterface $request, #[RouteArgument('id')] int $id) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        // 获取规则详情
        $rule = RuleModel::query()->where(['id' => $id, 'corp_id' => $corp->get('id')])->getOne();
        if (empty($rule)) {
            throw new LogicException("规则不存在");
        }

        // 拼接质检员工详细信息
        $staffUserList = StaffModel::query()
            ->select('userid,name')
            ->where(['in', 'userid', $rule->get('staff_userid_list')])
            ->getAll();
        $rule->append('staff_user_list', $staffUserList->toArray());

        // 拼接提醒员工详细信息
        $remindStaffUserList = StaffModel::query()
            ->select('userid,name')
            ->where(['in', 'userid', $rule->get('designate_remind_userid_list')])
            ->getAll();
        $rule->append('remind_staff_user_list', $remindStaffUserList->toArray());

        return $this->jsonResponse($rule);
    }

    /**
     * 添加规则
     */
    public function save(ServerRequestInterface $request) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $dto = new RuleDTO($request->getParsedBody());
        RuleModel::create(array_merge(['corp_id' => $corp->get('id')], $dto->toArray()));

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
        $rule->update($dto->toArray());

        return $this->jsonResponse();
    }

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
