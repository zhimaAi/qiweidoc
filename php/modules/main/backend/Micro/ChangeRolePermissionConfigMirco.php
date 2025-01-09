<?php

namespace Modules\Main\Micro;


use Basis\Nats\Message\Payload;
use Basis\Nats\Service\EndpointHandler;
use Modules\Main\Model\UserRoleModel;

/**
 * @author rand
 * @ClassName ChangeRolePermissionConfigController
 * @date 2025/1/210:45
 * @description
 */
class ChangeRolePermissionConfigMirco
{
    public function __construct(private string $payload)
    {

    }

    /**
     * @param Payload $payload
     * Notes: 更新角色权限配置
     * User: rand
     * Date: 2025/1/2 10:49
     * @return array
     * @throws \Throwable
     */
    public function handle(): array
    {
        $data = json_decode($this->payload, true);
        UserRoleModel::query()->where(["id" => $data["id"] ?? 0])->update(["permission_config" => $data["permission_config"]]);

        return [];
    }
}
