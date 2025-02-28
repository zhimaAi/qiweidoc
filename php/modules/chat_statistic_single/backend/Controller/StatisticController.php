<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\ChatStatisticSingle\Controller;


use Common\Controller\BaseController;
use Common\Job\Producer;
use Modules\Main\Model\CorpModel;
use Modules\ChatStatisticSingle\Consumer\StatisticsSingleChatConsumer;
use Modules\ChatStatisticSingle\Service\StatisticService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author rand
 * @ClassName IndexController
 * @date 2024/12/314:40
 * @description
 */
class StatisticController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取单聊统计配置
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
     * Notes: 获取单聊统计汇总数据
     * User: rand
     * Date: 2024/12/2 10:35
     * @return ResponseInterface
     */
    public function getTotal(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $result = StatisticService::getTotal($currentCorp, $request->getQueryParams());

        return $this->jsonResponse($result);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 获取单聊统计列表
     * User: rand
     * Date: 2024/12/2 10:23
     * @return ResponseInterface
     * @throws Throwable
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
        Producer::dispatch(StatisticsSingleChatConsumer::class, ['corp' => $currentCorp]);

        return $this->jsonResponse();
    }


}
