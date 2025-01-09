<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Yii;
use LogicException;
use Modules\Main\DTO\CreateUserBaseDTO;
use Modules\Main\DTO\UpdateUserInfoBaseDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\UserModel;
use Modules\Main\Model\UserRoleModel;
use Yiisoft\Arrays\ArrayHelper;

class UserService
{
    public static function init()
    {
        $userRole = UserRoleModel::query()->getAll()->toArray();
        $userRoleId = array_column($userRole,"id");

        $roleList = [
            [
                "id"=>EnumUserRoleType::NORMAL_STAFF->value,
                "role_name"=>"普通员工"
            ],[
                "id"=>EnumUserRoleType::ADMIN->value,
                "role_name"=>"管理员"
            ],[
                "id"=>EnumUserRoleType::SUPPER_ADMIN->value,
                "role_name"=>"超级管理员"
            ],[
                "id"=>EnumUserRoleType::VISITOR->value,
                "role_name"=>"游客账号"
            ],
        ];

        foreach ($roleList as $item) {
            if (!in_array($item["id"],$userRoleId)){
                $sql = "INSERT INTO \"main\".\"user_role\" (\"id\",  \"role_name\", \"permission_config\") VALUES ('".$item["id"]."', '".$item["role_name"]."', '[]')";
                Yii::db()->createCommand($sql)->execute();
            }
        }
    }

    public static function updateCurrentUserInfo(UserModel $currentUser, UpdateUserInfoBaseDTO $updateUserInfoDTO): void
    {

        if ($updateUserInfoDTO->password != $updateUserInfoDTO->repeatPassword) {
            throw new LogicException('确认密码不正确');
        }

        $hisUserInfo = UserModel::query()->where(["account" => $updateUserInfoDTO->username])->getOne();

        if (!empty($hisUserInfo) && $hisUserInfo->get("id") != $currentUser->get("id")) {
            throw new LogicException('登陆账号重复');
        }

        $currentUser->update([
            'password' => password_hash($updateUserInfoDTO->password, PASSWORD_DEFAULT, ['cost' => 12]),
            'account' => $updateUserInfoDTO->username,
        ]);
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 演示账户列表
     * User: rand
     * Date: 2024/12/11 20:13
     * @return array
     */
    public static function demoUserList(CorpModel $corp, $data)
    {

        $list = UserModel::query()->where(["corp_id" => $corp->get("id"), "role_id" => EnumUserRoleType::VISITOR->value])->orderBy(["created_at" => "DESC"])->paginate($data["page"] ?? 1, $data["size"] ?? 10);

        //员工角色列表
        $userRoleList = UserRoleModel::query()->select(["id", "role_name"])->getAll()->toArray();
        $userRoleListIndex = ArrayHelper::index($userRoleList, "id");

        foreach ($list["items"] as $item) {
            $item->append('role_info', $userRoleListIndex[$item->get("role_id")]??[]);
        }

        return $list;

    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 演示账户保存
     * User: rand
     * Date: 2024/12/11 20:14
     * @return array
     */
    public static function demoUserSave(CorpModel $corp, CreateUserBaseDTO $data)
    {

        $updateData = [
            "corp_id" => $corp->get("id"),
            "userid" => "",
            "account" => $data->account,
            "role_id" => EnumUserRoleType::VISITOR->value,
            "exp_time" => $data->expTime,
            "description" => $data->description
        ];


        if (!empty($data->id)) {

            UserModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data->id])->update($updateData);

        } else {
            //需要修改账号密码
            if (!empty($data->password) && !empty($data->verifyPassword) && $data->password != $data->verifyPassword) {
                throw new LogicException('确认密码不正确');
            }

            $updateData["password"] = password_hash($data->password, PASSWORD_DEFAULT, ['cost' => 12]);

            //检查一下有没有相同登陆账户名的账号
            $hisData = UserModel::query()->where(["corp_id" => $corp->get("id"), "role_id" => EnumUserRoleType::VISITOR->value, "account" => $data->account])->getOne();
            if (!empty($hisData)) {
                throw new LogicException("存在相同的账户名");
            }

            UserModel::create($updateData);
        }

        return [];
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 更新登陆权限
     * User: rand
     * Date: 2024/12/16 12:10
     * @return void
     * @throws \Throwable
     */
    public static function demoUserChangeLogin(CorpModel $corp, $data)
    {


        $updateData = [
            "can_login" => $data["can_login"] ?? 0
        ];
        UserModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->update($updateData);
    }

    /**
     * @param CorpModel $corp
     * @param $data
     * Notes: 删除账号
     * User: rand
     * Date: 2024/12/16 12:12
     * @return void
     * @throws \Throwable
     */
    public static function demoUserDelete(CorpModel $corp, $data)
    {
        UserModel::query()->where(["corp_id" => $corp->get("id"), "id" => $data["id"]])->deleteAll();

    }
}
