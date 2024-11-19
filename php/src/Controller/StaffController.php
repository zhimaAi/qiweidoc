<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\Libraries\Core\Http\BaseController;
use App\Models\CorpModel;
use App\Services\StaffService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
}
