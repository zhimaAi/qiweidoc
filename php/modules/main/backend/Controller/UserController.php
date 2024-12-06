<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Modules\Main\DTO\UpdateUserInfoBaseDTO;
use Modules\Main\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

class UserController extends BaseController
{
    /**
     * 获取当前登录用户信息
     */
    public function getCurrentUserInfo(ServerRequestInterface $request): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);
        $currentUserInfo->unset('password');

        return $this->jsonResponse($currentUserInfo);
    }

    /**
     * 修改当前登录用户信息
     */
    public function updateCurrentUserInfo(ServerRequestInterface $request): ResponseInterface
    {
        $updateUserInfoDTO = new UpdateUserInfoBaseDTO($request->getParsedBody());
        $currentUserInfo = $request->getAttribute(Authentication::class);

        UserService::updateCurrentUserInfo($currentUserInfo, $updateUserInfoDTO);

        return $this->jsonResponse();
    }
}
