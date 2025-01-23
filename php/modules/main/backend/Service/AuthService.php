<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Basis\Nats\Message\Payload;
use Common\Module;
use Common\Yii;
use Exception;
use Firebase\JWT\JWT;
use LogicException;
use Modules\Main\DTO\CodeLoginBaseDTO;
use Modules\Main\DTO\PasswordLoginBaseDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserModel;
use Psr\Http\Message\ServerRequestInterface;
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
        return Yii::cache()->getOrSet('jwt_key', function () {
            return Random::string();
        });
    }

    /**
     * 获取jwt过期时间
     */
    public static function getJwtExp(): int
    {
        return time() + 3600 * 24;
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
                throw new LogicException("非企业成员不支持登录");
            }
        } catch (LogicException $e) {
            throw new LogicException($e->getMessage());
        } catch (Throwable $e) {
            Yii::logger()->warning($e);
            throw new LogicException('获取用户登录身份信息失败');
        }

        //查一下表内员工
        $staffUserInfo = StaffModel::query()->where([
            'corp_id' => $codeLoginDTO->corpId,
            'userid' => $wechatUserInfo['userid']
        ])->getOne();

        $accountData = [
            'corp_id' => $codeLoginDTO->corpId,
            'userid' => $wechatUserInfo['userid'],
            'account' => mb_substr($corpInfo->get('id') . "_" . $wechatUserInfo['userid'], 0, 32), //生成一个默认登录用户名
            'role_id' => !empty($staffUserInfo)?$staffUserInfo->get("role_id"):EnumUserRoleType::NORMAL_STAFF->value,
        ];

        //如果当前企业没有超级管理员，把当前创建的账号设置为超级管理员账户
        $superAdminUser = UserModel::query()->where(['corp_id' => $codeLoginDTO->corpId, "role_id" => EnumUserRoleType::SUPPER_ADMIN->value])->getOne();
        if (empty($superAdminUser)) {
            $accountData["role_id"] = EnumUserRoleType::SUPPER_ADMIN->value;
        }

        // 获取或创建新用户
        $userInfo = UserModel::firstOrCreate(['and',
            ['corp_id' => $codeLoginDTO->corpId],
            ['userid' => $wechatUserInfo['userid']],
        ], $accountData);

        //如果不是游客账号，验证登陆权限
        $moduleConfig = Module::getLocalModuleConfig("user_permission");
        if ($userInfo->get("role_id") != EnumUserRoleType::VISITOR->value && isset($moduleConfig['paused']) && !($moduleConfig["paused"])) {
            $checkData = [
                "role_id" => $userInfo->get("role_id"),
                "permission_key" => "main.user_login.list",
                "corp_id" => $accountData["corp_id"]
            ];

            //验证当前账户权限
            $permissionCheckRes = false;
            Yii::getNatsClient()->request('user_permission.check_permission', json_encode($checkData), function (Payload $response) use (&$permissionCheckRes) {
                $permissionRes = json_decode($response, true);
                $permissionCheckRes = $permissionRes["res"];
            });

            if (!$permissionCheckRes) {
                throw new LogicException('当前账户无登陆权限');
            }
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
        if ($userInfo->get("role_id") == EnumUserRoleType::VISITOR->value && $userInfo->get("can_login") == 0) {
            throw new LogicException("当前账户无登陆权限");
        }

        //验证密码
        if (!password_verify($passwordLoginDTO->password, $userInfo->get('password'))) {
            throw new LogicException('用户名或密码错误');
        }

        //如果是游客账户登陆，继续验证一下登陆有效期
        if ($userInfo->get("role_id") == EnumUserRoleType::VISITOR->value && $userInfo->get("exp_time") > 0 && $userInfo->get("exp_time") < time()) {
            throw new LogicException('当前账户超过可登陆有效期');
        }

        //如果不是游客账号，验证登陆权限
        $moduleConfig = Module::getLocalModuleConfig("user_permission");
        if ($userInfo->get("role_id") != EnumUserRoleType::VISITOR->value && isset($moduleConfig['paused']) && !($moduleConfig["paused"])) {
            $checkData = [
                "role_id" => $userInfo->get("role_id"),
                "permission_key" => "main.user_login.list",
                "corp_id" => $userInfo->get("corp_id")
            ];

            //验证当前账户权限
            $permissionCheckRes = false;
            Yii::getNatsClient()->request('user_permission.check_permission', json_encode($checkData), function (Payload $response) use (&$permissionCheckRes) {
                $permissionRes = json_decode($response, true);
                $permissionCheckRes = $permissionRes["res"];
            });

            if (!$permissionCheckRes) {
                throw new LogicException('当前账户无登陆权限');
            }
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

    public static function saveLoginDomain(ServerRequestInterface $request): void
    {
        $server = $request->getServerParams();
        $proto = $server['HTTP_X_FORWARDED_PROTO'] ?? 'http';
        $host = $server['HTTP_X_FORWARDED_HOST'] ?? '127.0.0.1';

        Yii::cache()->psr()->set("login_domain", "{$proto}://{$host}");
    }

    public static function getLoginDomain(): string
    {
        return Yii::cache()->getOrSet('login_domain', function () {
            return "http://127.0.0.1";
        });
    }
}
