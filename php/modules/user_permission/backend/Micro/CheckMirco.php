<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Micro;

use Basis\Nats\Message\Payload;
use Basis\Nats\Service\EndpointHandler;
use Modules\Main\Model\UserRoleModel;
use Modules\UserPermission\Model\OtherRoleModel;

class CheckMirco
{
    public function __construct(private string $payload)
    {

    }

    public function handle(): array
    {
        $data = json_decode($this->payload, true);

        if (empty($data["role_id"]) || empty($data["permission_key"])) {
            return [
                "res" => false,
            ];
        }

        //角色ID小于100 ，系统默认角色
        if ($data["role_id"] < 100) {
            $query = UserRoleModel::query()->where(["id" => $data["role_id"]]);
        } else {
            $query = OtherRoleModel::query()->where([
                "corp_id" => $data["corp_id"] ?? "",
                "id" => $data["role_id"] ?? "",
            ]);
        }
        $roleInfo = $query->getOne();

        //没找到角色，退出
        if (empty($roleInfo)) {
            return [
                "res" => false,
            ];
        }

        //获取当前角色的权限列表
        $permissionConfig = $roleInfo->toArray()["permission_config"] ?? [];

        //路由不在角色权限内，退出
        if (!in_array($data["permission_key"], $permissionConfig)) {
            return [
                "res" => false,
            ];
        }

        return [
            "res" => true,
        ];
    }
}
