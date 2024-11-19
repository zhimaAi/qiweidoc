<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\DTO\UpdateUserInfoDTO;
use App\Libraries\Core\Http\BaseController;
use App\Services\UserService;
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

        return $this->jsonResponse($currentUserInfo);
    }

    /**
     * 修改当前登录用户信息
     */
    public function updateCurrentUserInfo(ServerRequestInterface $request): ResponseInterface
    {
        $updateUserInfoDTO = new UpdateUserInfoDTO($request->getParsedBody());
        $currentUserInfo = $request->getAttribute(Authentication::class);

        UserService::updateCurrentUserInfo($currentUserInfo, $updateUserInfoDTO);

        return $this->jsonResponse();
    }
}
