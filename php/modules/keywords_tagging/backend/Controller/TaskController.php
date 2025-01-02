<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Controller;

use Common\Controller\BaseController;
use Modules\KeywordsTagging\DTO\MarkTagTaskDTO;
use Modules\KeywordsTagging\DTO\QueryRuleTriggerLogDTO;
use Modules\KeywordsTagging\DTO\UpMarkTagTaskSwitchDTO;
use Modules\KeywordsTagging\Service\TaskService;
use Modules\Main\Model\CorpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * @details 关键词打标签任务 服务
 * @author ivan
 * @date 2024/12/23 14:34
 * Class TaskController
 */
class TaskController extends BaseController
{
    /**
     * 关键词打标签 任务列表
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:35
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = TaskService::list($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * 删除关键词打标签
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:35
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $upDto=new UpMarkTagTaskSwitchDTO($request->getParsedBody());
        TaskService::delete($corp, $upDto);

        return $this->jsonResponse();
    }

    /**
     * 保存关键词打标签任务
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 14:36
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $taskDto= new MarkTagTaskDTO($request->getParsedBody());

        TaskService::save($corp, $taskDto);

        return $this->jsonResponse();

    }

    /**
     * 任务详情信息
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 18:46
     */
    public function info(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $upDto=new UpMarkTagTaskSwitchDTO($request->getQueryParams());
        $taskInfo= TaskService::getTaskInfo($corp, $upDto);

        return $this->jsonResponse($taskInfo);
    }


    /**
     * 任务统计信息
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/24 11:00
     */
    public function statistics(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $taskInfo= TaskService::getStatistics($corp,$request->getQueryParams());
        return $this->jsonResponse($taskInfo);
    }


    /**
     * @details 修改开关状态
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/23 15:15
     */
    public function changeStatus(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $queryDto=new UpMarkTagTaskSwitchDTO($request->getParsedBody());
        TaskService::changeSwitch($corp, $queryDto);

        return $this->jsonResponse();
    }

    /**
     * 规则触发日志列表
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @author ivan
     * @date 2024/12/24 10:51
     */
    public function RuleTriggerLogList(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $queryDto=new QueryRuleTriggerLogDTO($request->getQueryParams());
        $result= TaskService::getRuleTriggerLogList($corp, $queryDto);

        return $this->jsonResponse($result);
    }
}
