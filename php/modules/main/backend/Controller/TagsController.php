<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\TagsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

class TagsController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 员工标签列表
     * User: rand
     * Date: 2024/11/8 14:47
     * @return ResponseInterface
     */
    public function staff(ServerRequestInterface $request): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);

        $res = TagsService::staff($currentUserInfo);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 客户标签列表
     * User: rand
     * Date: 2024/12/10 16:03
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function customer(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $res = TagsService::customer($currentCorp);

        return $this->jsonResponse($res);
    }
}
