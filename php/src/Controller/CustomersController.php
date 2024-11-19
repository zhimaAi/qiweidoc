<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\Consumers\SyncCstTagConsumer;
use App\Libraries\Core\Http\BaseController;
use App\Models\CorpModel;
use App\Services\CustomersService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

class CustomersController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 同步客户、标签
     * User: rand
     * Date: 2024/11/7 18:31
     * @return ResponseInterface
     */
    public function sync(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        // 同步客户标签，标签同步完了再去处理客户同步，
        SyncCstTagConsumer::dispatch(['corpInfo' => $currentCorpInfo]);

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 客户列表
     * User: rand
     * Date: 2024/11/7 18:31
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function list(ServerRequestInterface $request)
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        $res = CustomersService::list($currentCorpInfo, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

}
