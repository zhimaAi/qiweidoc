<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticGroup\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use Modules\Main\Model\CorpModel;
use Modules\ChatStatisticGroup\Consumer\StatisticsGroupChatConsumer;
use Modules\ChatStatisticGroup\Service\StatisticService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Notes:群聊统计
 * User: rand
 * Date: 2024/12/27 17:22
 */
class StatisticController extends BaseController
{
    /**
     * @param ServerRequestInterface $request
     * Notes: 获取群聊统计配置
     * User: rand
     * Date: 2024/12/2 10:35
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getConfig(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $result = StatisticService::getConfig($currentCorp);

        return $this->jsonResponse($result);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 保存单聊统计配置
     * User: rand
     * Date: 2024/12/2 10:35
     * @return ResponseInterface
     */
    public function saveConfig(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        $param = $request->getParsedBody();

        StatisticService::saveConfig($currentCorp, $param);

        return $this->jsonResponse();
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 获取群聊统计列表
     * User: rand
     * Date: 2024/12/27 17:24
     * @return ResponseInterface
     * @throws \Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws \Yiisoft\Db\Exception\InvalidConfigException
     */
    public function getList(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();

        $result = StatisticService::getList($currentCorp, $params);

        return $this->jsonResponse($result);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 获取单聊统计明细
     * User: rand
     * Date: 2024/12/2 10:35
     * @return ResponseInterface
     */
    public function getDetail(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();

        $result = StatisticService::getDetail($currentCorp, $params);

        return $this->jsonResponse($result);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 主动更新统计
     * User: rand
     * Date: 2024/12/25 18:53
     * @return ResponseInterface
     */
    public function stat(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        Producer::dispatch(StatisticsGroupChatConsumer::class, ['corp' => $currentCorp]);

        return $this->jsonResponse();
    }
}
