<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\DTO\CodeLoginDTO;
use App\DTO\PasswordLoginDTO;
use App\Libraries\Core\Http\BaseController;
use App\Services\AuthService;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthController extends BaseController
{
    /**
     * 扫码授权登录
     *
     * @throws LogicException
     */
    public function codeLogin(ServerRequestInterface $request): ResponseInterface
    {
        $codeLoginDTO = new CodeLoginDTO($request->getParsedBody());

        $jwtToken = AuthService::generateJwtByCallbackAuthCode($codeLoginDTO);

        return $this->jsonResponse(['token' => $jwtToken]);
    }

    /**
     * 账号密码登录
     */
    public function passwordLogin(ServerRequestInterface $request): ResponseInterface
    {
        $passwordLoginDTO = new PasswordLoginDTO($request->getParsedBody());

        $jwtToken = AuthService::generateJwtByPassword($passwordLoginDTO);

        return $this->jsonResponse(['token' => $jwtToken]);
    }
}
