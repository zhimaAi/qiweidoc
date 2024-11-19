<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 用户信息
 */

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;
use App\Services\AuthService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

class UserModel extends BaseModel implements IdentityInterface, IdentityWithTokenRepositoryInterface
{
    public function getTableName(): string
    {
        return "users";
    }

    protected function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function casts(): array
    {
        return [];
    }

    /**
     * 用于认证的唯一标识名
     */
    public function getId() :?string
    {
        return 'id';
    }

    /**
     * 解析token拿到登录用户信息
     */
    public function findIdentityByToken(string $token, string $type = null): ?IdentityInterface
    {
        try {
            $decoded = JWT::decode($token, new Key(AuthService::getJwtKey(), 'HS256'));
            /** @var UserModel $result */
            $result = self::query()->where(['id' => $decoded->id])->getOne();
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }
}
