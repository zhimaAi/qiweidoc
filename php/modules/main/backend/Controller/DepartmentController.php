<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use Modules\Main\Consumer\SyncDepartmentConsumer;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\DepartmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

/**
 * @author rand
 * @ClassName DepartmentController
 * @date 2024/11/114:16
 * @description
 */
class DepartmentController extends BaseController
{
    /**
     * @param ServerRequestInterface $request
     * Notes: 同步更新部门列表
     * User: rand
     * Date: 2024/11/1 14:17
     * @return ResponseInterface
     */
    public function sync(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        Producer::dispatch(SyncDepartmentConsumer::class, ['corp' => $currentCorpInfo]);

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 部门列表
     * User: rand
     * Date: 2024/11/1 14:17
     * @return ResponseInterface
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);

        $ret = DepartmentService::list($currentUserInfo);

        return $this->jsonResponse($ret);

    }

}
