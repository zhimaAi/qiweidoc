<?php

namespace Modules\Main\Micro;


use Basis\Nats\Message\Payload;
use Basis\Nats\Service\EndpointHandler;
use Modules\Main\Model\StaffModel;
use Modules\Main\Model\UserModel;

/**
 * @author rand
 * @ClassName ChangeUserRoleController
 * @date 2025/1/210:20
 * @description
 */
class ChangeStaffRoleMirco
{
    public function __construct(private string $payload)
    {

    }

    /**
     * @param Payload $payload
     * Notes: 更新账户角色
     * User: rand
     * Date: 2025/1/2 10:49
     * @return array
     * @throws \Throwable
     */
    public function handle(): array
    {
        $data = json_decode($this->payload, true);

        if (!empty($data["staff_userid"]) && !empty($data["new_role_id"])) {//按员工更新
            //更新账户角色
            StaffModel::query()->where([
                "corp_id" => $data["corp_id"]
            ])->andWhere(["in", "userid", $data["staff_userid"]])->update([
                "role_id" => $data["new_role_id"]
            ]);

            //用户表更新一下
            UserModel::query()->where([
                "corp_id" => $data["corp_id"]
            ])->andWhere(["in", "userid", $data["staff_userid"]])->update([
                "role_id" => $data["new_role_id"]
            ]);
        } else {//按角色更新
            //更新账户角色
            StaffModel::query()->where([
                "corp_id" => $data["corp_id"],
                "role_id" => $data["role_id"]
            ])->update([
                "role_id" => $data["new_role_id"]
            ]);

            //用户表更新一下
            UserModel::query()->where([
                "corp_id" => $data["corp_id"],
                "role_id" => $data["role_id"]
            ])->update([
                "role_id" => $data["new_role_id"]
            ]);
        }

        return [];
    }
}
