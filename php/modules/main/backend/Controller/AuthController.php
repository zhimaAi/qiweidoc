<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Yii;
use LogicException;
use Modules\Main\DTO\CodeLoginBaseDTO;
use Modules\Main\DTO\PasswordLoginBaseDTO;
use Modules\Main\Service\AuthService;
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
        $codeLoginDTO = new CodeLoginBaseDTO($request->getParsedBody());

        $jwtToken = AuthService::generateJwtByCallbackAuthCode($codeLoginDTO);

        AuthService::saveLoginDomain($request);

        return $this->jsonResponse(['token' => $jwtToken]);
    }

    /**
     * 账号密码登录
     */
    public function passwordLogin(ServerRequestInterface $request): ResponseInterface
    {
        $passwordLoginDTO = new PasswordLoginBaseDTO($request->getParsedBody());

        $jwtToken = AuthService::generateJwtByPassword($passwordLoginDTO);

        AuthService::saveLoginDomain($request);

        return $this->jsonResponse(['token' => $jwtToken]);
    }
}
