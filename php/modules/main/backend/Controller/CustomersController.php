<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use Modules\Main\Consumer\SyncCustomersConsumer;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\CustomersService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponse;

class CustomersController extends BaseController
{
    /**
     * @param ServerRequestInterface $request
     * Notes: 同步客户、标签
     * User: rand
     * Date: 2024/11/7 18:31
     * @return ResponseInterface
     * @throws Throwable
     */
    public function sync(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        Producer::dispatch(SyncCustomersConsumer::class, ['corp' => $currentCorpInfo]);

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 客户列表
     * User: rand
     * Date: 2024/11/7 18:31
     * @return DataResponse
     * @throws Throwable
     */
    public function list(ServerRequestInterface $request)
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        $res = CustomersService::list($currentCorpInfo, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * 按客户查询聊天记录时获取有会话记录的客户列表
     * @throws Throwable
     */
    public function hasConversationList(ServerRequestInterface $request)
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $res = CustomersService::hasConversationList($currentCorpInfo, $page, $size, $params);

        return $this->jsonResponse($res);
    }
}
