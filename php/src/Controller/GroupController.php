<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\Consumers\SyncGroupConsumer;
use App\Libraries\Core\Http\BaseController;
use App\Models\CorpModel;
use App\Services\GroupService;
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

        SyncGroupConsumer::dispatch(['corpInfo' => $currentCorpInfo]);

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
