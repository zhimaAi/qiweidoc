<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\StaffService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

/**
 * @author rand
 * @ClassName StaffController
 * @date 2024/11/116:05
 * @description
 */
class StaffController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 员工列表
     * User: rand
     * Date: 2024/11/7 18:32
     * @return ResponseInterface
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = StaffService::list($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 变更账户可登陆状态
     * User: rand
     * Date: 2024/12/11 19:45
     * @return ResponseInterface
     */
    public function changeLogin(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $currentUserInfo = $request->getAttribute(Authentication::class);

        StaffService::changeLogin($corp, $currentUserInfo,$request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 变更账户角色
     * User: rand
     * Date: 2024/12/11 19:45
     * @return ResponseInterface
     */
    public function changeRole(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $currentUserInfo = $request->getAttribute(Authentication::class);
        StaffService::changeRole($corp, $currentUserInfo,$request->getParsedBody());

        return $this->jsonResponse();
    }
}
