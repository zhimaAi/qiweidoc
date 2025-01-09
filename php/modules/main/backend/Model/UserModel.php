<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

/**
 * 用户信息
 */

namespace Modules\Main\Model;

use Common\DB\BaseModel;
use Common\Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Service\AuthService;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

class UserModel extends BaseModel implements IdentityInterface, IdentityWithTokenRepositoryInterface
{
    public function getTableName(): string
    {
        return "main.users";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "corp_id" => 'string',
            "userid" => 'string',
            "account" => 'string',
            "password" => 'string',
            "created_at" => 'string',
            "updated_at" => 'string',
            "role_id" => 'int',
            "exp_time" => 'int',
            "can_login" => 'int',
            "description" => 'string',
        ];
    }

    /**
     * 用于认证的唯一标识名
     */
    public function getId() :?string
    {
        return 'id';
    }

    public function getUserInfoById(int $id): ?UserModel
    {
        $key = "user:{$id}";

        return Yii::cache()->getOrSet($key, function () use ($id) {
            return self::query()->where(['id' => $id])->getOne();
        }, 60);
    }

    /**
     * 解析token拿到登录用户信息
     */
    public function findIdentityByToken(string $token, string $type = null): ?IdentityInterface
    {
        try {
            $decoded = JWT::decode($token, new Key(AuthService::getJwtKey(), 'HS256'));

            return $this->getUserInfoById($decoded->id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
