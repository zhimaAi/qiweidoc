<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Micro;

use Modules\UserPermission\Model\OtherRoleModel;

class GetRoleListMirco
{

    public function __construct(private string $payload)
    {

    }

    public function handle(): array
    {
        $data = json_decode($this->payload, true);

        if (empty($data["corp_id"])) {
            return [
                "res" => []
            ];
        }

        $roleList = OtherRoleModel::query()->where(["corp_id" => $data["corp_id"]])->getAll();

        if (!empty($roleList)) {
            return [
                "res" => $roleList->toArray()
            ];
        }

        return [
            "res" => [],
        ];
    }
}
