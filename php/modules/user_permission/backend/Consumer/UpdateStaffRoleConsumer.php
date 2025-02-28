<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Consumer;


use Common\Yii;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Modules\UserPermission\Service\PermissionService;
use Yiisoft\Arrays\ArrayHelper;

/**
 * @author rand
 * @ClassName UpdateStaffRoleConsumer
 * @date 2024/12/3109:17
 * @description
 */
class UpdateStaffRoleConsumer
{

    private readonly CorpModel $corp;

    public function __construct($corp)
    {
        $this->corp = $corp;
    }

    public function handle()
    {

        //获取企业下的所有角色
        $allRoleList = PermissionService::getList($this->corp,[]);
        $roleIndex = ArrayHelper::index($allRoleList, "id");
        Yii::cache()->psr()->set("corp_role_list." . $this->corp->get("id"), json_encode($roleIndex));


        //获取企业下所有员工列表
        $allStaffList = StaffModel::query()->select(["name", "role_id", "userid"])->where(["corp_id" => $this->corp->get("id")])->getAll();

        if (!empty($allStaffList)) {
            foreach ($allStaffList->toArray() as $item) {
                Yii::cache()->psr()->set("corp_staff_info." . $this->corp->get("id") . "." . $item["userid"], json_encode($item));
            }
        }
    }

}
