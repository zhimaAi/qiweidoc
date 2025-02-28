<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Controller;

use Common\Controller\BaseController;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\UserPermission\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

/**
 * Notes:权限控制
 * User: rand
 * Date: 2024/12/30 17:18
 */
class PermissionController extends BaseController
{


    /**
     * @param ServerRequestInterface $request
     * Notes: 获取角色列表
     * User: rand
     * Date: 2024/12/30 18:27
     * @return ResponseInterface
     */
    public function getList(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $res = PermissionService::getList($currentCorp,$request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 角色下员工列表
     * User: rand
     * Date: 2024/12/31 09:24
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function userList(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $res = PermissionService::getUserList($currentCorp,$request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取所有权限列表
     * User: rand
     * Date: 2024/12/31 11:41
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function permissionList(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $res = PermissionService::permissionList($currentCorp);

        return $this->jsonResponse($res);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 保存角色配置
     * User: rand
     * Date: 2024/12/30 18:27
     * @return ResponseInterface
     */
    public function saveRole(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        $param = $request->getParsedBody();

        PermissionService::saveRole($currentCorp, $param);


        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 切换角色
     * User: rand
     * Date: 2024/12/30 18:27
     * @return ResponseInterface
     */
    public function changeRole(ServerRequestInterface $request): ResponseInterface
    {

        $currentCorp = $request->getAttribute(CorpModel::class);
        $param = $request->getParsedBody();

        if ($param["new_role_id"] == EnumUserRoleType::SUPPER_ADMIN) {
            throw new \Exception("不可以设置为超级管理员");
        }

        PermissionService::changeRole($currentCorp, $param);


        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 角色删除
     * User: rand
     * Date: 2024/12/31 10:11
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {

        //验证一下管理员权限，只有管理员和超级管理员才有权限编辑角色配置
        $currentUserInfo = $request->getAttribute(Authentication::class);
        if (!in_array($currentUserInfo->get("role_id"), [EnumUserRoleType::SUPPER_ADMIN, EnumUserRoleType::ADMIN])) {
            throw new \Exception("您不是管理员，不可进行此操作");
        }

        $currentCorp = $request->getAttribute(CorpModel::class);
        $param = $request->getParsedBody();

        PermissionService::delete($currentCorp, $param["role_id"]??0);


        return $this->jsonResponse();
    }


}
