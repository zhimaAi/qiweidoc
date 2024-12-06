<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
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
}
