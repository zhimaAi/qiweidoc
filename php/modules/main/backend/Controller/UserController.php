<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Modules\Main\DTO\CreateUserBaseDTO;
use Modules\Main\DTO\UpdateUserInfoBaseDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\UserRoleModel;
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

    /**
     * @param ServerRequestInterface $request
     * Notes: 演示账户列表
     * User: rand
     * Date: 2024/12/11 20:12
     * @return ResponseInterface
     */
    public function demoUserList(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = UserService::demoUserList($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 演示账户保存
     * User: rand
     * Date: 2024/12/11 20:11
     * @return ResponseInterface
     */
    public function demoUserSave(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $updateUserInfoDTO = new CreateUserBaseDTO($request->getParsedBody());


        $res = UserService::demoUserSave($corp, $updateUserInfoDTO);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 更新配置
     * User: rand
     * Date: 2024/12/16 12:06
     * @return ResponseInterface
     * @throws \Exception
     */
    public function demoUserChangeLogin(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        UserService::demoUserChangeLogin($corp, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 删除账号
     * User: rand
     * Date: 2024/12/16 12:11
     * @return ResponseInterface
     */
    public function demoUserDelete(ServerRequestInterface $request): ResponseInterface
    {

        $corp = $request->getAttribute(CorpModel::class);

        UserService::demoUserDelete($corp, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取角色列表
     * User: rand
     * Date: 2024/12/16 17:38
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function userRoleList(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponse(UserRoleModel::query()->getAll());
    }

}
