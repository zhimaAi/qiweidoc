<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use Modules\Main\Consumer\SyncGroupConsumer;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\GroupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponse;

class GroupController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 同步群聊
     * User: rand
     * Date: 2024/11/7 18:31
     * @return ResponseInterface
     * @throws Throwable
     */
    public function sync(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        Producer::dispatch(SyncGroupConsumer::class, ['corp' => $currentCorpInfo]);

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 群列表
     * User: rand
     * Date: 2024/11/7 18:32
     * @return DataResponse
     * @throws Throwable
     */
    public function list(ServerRequestInterface $request): DataResponse
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = GroupService::list($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

}
