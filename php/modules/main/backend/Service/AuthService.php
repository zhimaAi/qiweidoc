<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Yii;
use Exception;
use Firebase\JWT\JWT;
use LogicException;
use Modules\Main\DTO\CodeLoginBaseDTO;
use Modules\Main\DTO\PasswordLoginBaseDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\UserModel;
use Throwable;
use Yiisoft\Security\Random;

class AuthService
{
    /**
     * 获取jwt key
     * 不固定，第一次生成后放入缓存，缓存失效后重新生成
     * @throws Throwable
     */
    public static function getJwtKey(): string
    {
        $jwtKey = Yii::cache()->psr()->get('jwt_key');
        if (empty($jwtKey)) {
            $jwtKey = Random::string();
            Yii::cache()->psr()->set('jwt_key', $jwtKey);
        }

        return $jwtKey;
    }

    /**
     * 获取jwt过期时间
     */
    public static function getJwtExp(): int
    {
        return  time() + 3600 * 24;
    }

    /**
     * 通过企业回调的code信息生成jwt
     * @throws Throwable
     */
    public static function generateJwtByCallbackAuthCode(CodeLoginBaseDTO $codeLoginDTO): string
    {
        //获取企业信息
        /** @var CorpModel $corpInfo */
        $corpInfo = CorpModel::query()->where(['id' => $codeLoginDTO->corpId])->getOne();
        if (empty($corpInfo)) {
            throw new LogicException('企业信息不存在');
        }

        //获取用户在企微的身份信息
        try {
            $wechatUserInfo = $corpInfo->getWechatApi('/cgi-bin/auth/getuserinfo', ['code' => $codeLoginDTO->code]);
            if (empty($wechatUserInfo['userid'])) {
                throw new Exception("非企业成员不支持登录");
            }
        } catch (Throwable $e) {
            Yii::logger()->warning($e);

            throw new LogicException('获取用户登录身份信息失败');
        }

        $accountData = [
            'corp_id' => $codeLoginDTO->corpId,
            'userid' => $wechatUserInfo['userid'],
            'account' => mb_substr($corpInfo->get('id') . "_" . $wechatUserInfo['userid'], 0, 32), //生成一个默认登录用户名
            'role_id' => EnumUserRoleType::NORMAL_STAFF,
            'can_login' => 0
        ];

        //如果当前企业没有超级管理员，把当前创建的账号设置为超级管理员账户
        $superAdminUser = UserModel::query()->where(['corp_id' => $codeLoginDTO->corpId, "role_id" => EnumUserRoleType::SUPPER_ADMIN->value])->getOne();
        if (empty($superAdminUser)) {
            $accountData["role_id"] = EnumUserRoleType::SUPPER_ADMIN->value;
            $accountData["can_login"] = 1;
        }

        // 获取或创建新用户
        $userInfo = UserModel::firstOrCreate(['and',
            ['corp_id' => $codeLoginDTO->corpId],
            ['userid' => $wechatUserInfo['userid']],
        ], $accountData);

        if ($userInfo->get("can_login") == 0) {
            throw new LogicException("当前账户无登陆权限");
        }

        //生成jwt key
        $jwtKey = self::getJwtKey();
        $payload = [
            'id' => $userInfo->get('id'),
            'userid' => $userInfo->get('userid'),
            'corp_id' => $userInfo->get('corp_id'),
            'exp' => self::getJwtExp(),
        ];

        return JWT::encode($payload, $jwtKey, 'HS256');
    }

    /**
     * 通过账号密码生成jwt token
     * @throws Throwable
     */
    public static function generateJwtByPassword(PasswordLoginBaseDTO $passwordLoginDTO): string
    {
        //获取用户信息
        $userInfo = UserModel::query()->where(['account' => $passwordLoginDTO->username])->getOne();
        if (empty($userInfo)) {
            throw new LogicException('用户名或密码错误');
        }

        //账户是否可以登陆验证
        if ($userInfo->get("can_login") == 0) {
            throw new LogicException("当前账户无登陆权限");
        }

        //验证密码
        if (!password_verify($passwordLoginDTO->password, $userInfo->get('password'))) {
            throw new LogicException('用户名或密码错误');
        }

        //如果是游客账户登陆，继续验证一下登陆有效期
        if ($userInfo->get("role_id") == EnumUserRoleType::VISITOR && $userInfo->get("exp_time") > 0 && $userInfo->get("exp_time") < time()) {
            throw new LogicException('当前账户超过可登陆有效期');
        }

        //生成jwt key
        $jwtKey = self::getJwtKey();
        $payload = [
            'id' => $userInfo->get('id'),
            'userid' => $userInfo->get('userid'),
            'corp_id' => $userInfo->get('corp_id'),
            'exp' => self::getJwtExp(),
        ];

        return JWT::encode($payload, $jwtKey, 'HS256');
    }
}
