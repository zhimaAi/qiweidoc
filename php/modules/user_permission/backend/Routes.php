<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission;

use Basis\Nats\Message\Payload;
use Common\Cron;
use Common\Job\Consumer;
use Common\Micro;
use Common\RouterProvider;
use Common\Yii;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Library\Middlewares\CurrentCorpInfoMiddleware;
use Modules\Main\Library\Middlewares\UserRoleMiddleware;
use Modules\Main\Model\CorpModel;
use Modules\UserPermission\Consumer\UpdateStaffRoleConsumer;
use Modules\UserPermission\Controller\PermissionController;
use Modules\UserPermission\Cron\CheckExpireCron;
use Modules\UserPermission\Micro\CheckMirco;
use Modules\UserPermission\Micro\GetRoleListMirco;
use Modules\UserPermission\Model\RolePermissionDetailModel;
use Modules\UserPermission\Model\UserRoleDetailModel;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class Routes extends RouterProvider
{

    //初始权限定义
    const BaseRolePermission = [
        [
            "permission_index" => 0,
            "server_name" => "会话质检",
            "description" => "查看聊天记录",
            "permission_list" => [
                [
                    "title" => "会话质检",
                    "permission_key" => "session_archive",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看会话质检",
                            "permission_key" => "main.session_archive.list",
                        ]
                    ]
                ],
                [
                    "title" => "会话搜索",
                    "permission_key" => "session_search",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看会话搜索",
                            "permission_key" => "main.session_archive.search",
                        ]
                    ]
                ]
            ]
        ],
        [
            "permission_index" => 1,
            "server_name" => "功能插件",
            "description" => "开启和关闭插件，管理插件功能",
            "permission_list" => [
                [
                    "title" => "功能插件",
                    "permission_key" => "plug_module",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可开启和关闭插件",
                            "permission_key" => "main.plug_module.edit",
                        ]
                    ]
                ],
                [
                    "title" => "敏感词提醒",
                    "permission_key" => "hint_keywords",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看敏感词",
                            "permission_key" => "hint_keywords.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可创建和编辑敏感词规则",
                            "permission_key" => "hint_keywords.edit",
                        ]
                    ]
                ],
                [
                    "title" => "单聊超时提醒",
                    "permission_key" => "timeout_reply_single",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看单聊超时提醒",
                            "permission_key" => "timeout_reply_single.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可创建和编辑单聊超时提醒",
                            "permission_key" => "timeout_reply_single.edit",
                        ]
                    ]
                ],
                [
                    "title" => "群聊超时提醒",
                    "permission_key" => "timeout_reply_group",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看群聊超时提醒",
                            "permission_key" => "timeout_reply_group.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可创建和编辑群聊超时提醒",
                            "permission_key" => "timeout_reply_group.edit",
                        ]
                    ]
                ],
                [
                    "title" => "单聊统计",
                    "permission_key" => "chat_statistic_single",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看单聊统计和统计规则",
                            "permission_key" => "chat_statistic_single.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可编辑单聊统计规则",
                            "permission_key" => "chat_statistic_single.edit",
                        ]
                    ]
                ],
                [
                    "title" => "群聊统计",
                    "permission_key" => "chat_statistic_group",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看群聊统计和统计规则",
                            "permission_key" => "chat_statistic_group.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可编辑群聊统计规则",
                            "permission_key" => "chat_statistic_group.edit",
                        ]
                    ]
                ],
                [
                    "title" => "权限管理",
                    "permission_key" => "user_permission",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看权限功能",
                            "permission_key" => "user_permission.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可新增角色、编辑权限",
                            "permission_key" => "user_permission.edit",
                        ]
                    ]
                ],
                [
                    "title" => "关键词打标签",
                    "permission_key" => "keywords_tagging",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看打标签规则",
                            "permission_key" => "keywords_tagging.list",
                        ],
                        [
                            "status" => 1,
                            "description" => "可创建和编辑打标签规则",
                            "permission_key" => "keywords_tagging.edit",
                        ]
                    ]
                ],
                [
                    "title" => "企业高级设置",
                    "permission_key" => "custom_brand",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可编辑企业高级设置",
                            "permission_key" => "custom_brand.edit",
                        ],
                    ]
                ],
            ]
        ],
        [
            "permission_index" => 2,
            "server_name" => "客户管理",
            "description" => "查看客户信息和客户标签，创建客户标签",
            "permission_list" => [
                [
                    "title" => "客户管理",
                    "permission_key" => "customer_manager",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "查看客户信息",
                            "permission_key" => "main.customer_manager.list",
                        ]
                    ]
                ],
                [
                    "title" => "客户标签",
                    "permission_key" => "customer_tag",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可新增标签、管理标签",
                            "permission_key" => "main.customer_tag.edit",
                        ]
                    ]
                ]
            ]
        ],
        [
            "permission_index" => 3,
            "server_name" => "企业管理",
            "description" => "管理企业员工和群",
            "permission_list" => [
                [
                    "title" => "员工管理",
                    "permission_key" => "corp_staff",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可同步员工列表",
                            "permission_key" => "main.corp_staff.sync"
                        ]
                    ]
                ],
                [
                    "title" => "群管理",
                    "permission_key" => "corp_group",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可同步群",
                            "permission_key" => "main.corp_group.sync"
                        ]
                    ]
                ]
            ]
        ],
        [
            "permission_index" => 4,
            "server_name" => "企业设置",
            "description" => "基础功能管理",
            "permission_list" => [
                [
                    "title" => "配置信息",
                    "permission_key" => "corp_setting",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "编辑配置信息",
                            "permission_key" => "main.corp_setting.edit"
                        ]
                    ]
                ],
                [
                    "title" => "文件存储配置",
                    "permission_key" => "oss_config",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可查看文件存储配置",
                            "permission_key" => "main.oss_config.list"
                        ],
                        [
                            "status" => 1,
                            "description" => "编辑文件存储配置",
                            "permission_key" => "main.oss_config.edit"
                        ]
                    ]
                ],
                [
                    "title" => "账号管理",
                    "permission_key" => "user_config",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "设置登陆密码",
                            "permission_key" => "main.user_config.edit"
                        ]
                    ]
                ]
            ]
        ],
        [
            "permission_index" => 5,
            "server_name" => "用户权限",
            "description" => "可登陆会话存档后台",
            "permission_list" => [
                [
                    "title" => "用户登陆",
                    "permission_key" => "user_login",
                    "child" => [
                        [
                            "status" => 1,
                            "description" => "可登陆后台",
                            "permission_key" => "main.user_login.list"
                        ],
                    ]
                ]
            ]
        ],
    ];

    public function getGrpcRouters(): array
    {
        return [];
    }

    public function getHttpRouters(): array
    {
        return [
            Group::create("/api")
                ->middleware(Authentication::class)
                ->middleware(CurrentCorpInfoMiddleware::class)
                ->middleware(UserRoleMiddleware::class)
                ->routes(
                    Route::get('/list')->action([PermissionController::class, 'getList'])->defaults(["permission_key" => 'user_permission.list','filter_status'=>true]),//获取角色列表
                    Route::get('/user/list')->action([PermissionController::class, 'userList'])->defaults(["permission_key" => 'user_permission.list']),//角色下员工列表
                    Route::post('/save')->action([PermissionController::class, 'saveRole'])->defaults(["permission_key" => 'user_permission.edit']),//编辑角色
                    Route::post('/change/role')->action([PermissionController::class, 'changeRole'])->defaults(["permission_key" => 'user_permission.edit']),//切换员工角色
                    Route::post('/delete')->action([PermissionController::class, 'delete'])->defaults(["permission_key" => 'user_permission.edit']),//删除角色
                    Route::get('/permission/list')->action([PermissionController::class, 'permissionList'])->defaults(["permission_key" => 'user_permission.list']),//权限列表
                )
        ];
    }

    public function getConsoleRouters(): array
    {
        return [];
    }

    public function getConsumerRouters(): array
    {
        return [
            Consumer::name("update_staff_role")->count(1)->action(UpdateStaffRoleConsumer::class),
        ];
    }

    public function init(): void
    {

        $corp = CorpModel::query()->getOne();
        // Producer::dispatchCron(UpdateStaffRoleConsumer::class, ["corp" => $corp], '30 seconds');

        //管理员账户角色权限，（管理员，超级管理员）
        $adminUserRolePermissionList = [];
        //普通员工账户权限
        $normalStaffUserRolePermissionList = [];

        //编辑权限列表入库
        foreach (self::BaseRolePermission as $item) {
            foreach ($item["permission_list"] as $node) {
                $data = [
                    "corp_id" => $corp->get("id"),
                    "permission_index" => $item["permission_index"],
                    "server_name" => $item["server_name"],
                    "description" => $item["description"],
                    "permission_node_title" => $node["title"],
                    "permission_detail" => $node,
                    "permission_key" => $node["permission_key"],
                ];

                $where = [
                    "corp_id" => $corp->get("id"),
                    "permission_key" => $node["permission_key"],
                ];

                RolePermissionDetailModel::updateOrCreate($where, array_merge($where,$data));

                //处理一下默认账户的权限列表
                foreach ($node["child"] as $child) {
                    //管理员账户 含管理员，超级管理员，给全部权限·
                    $adminUserRolePermissionList[] = $child["permission_key"];

                    //普通员工角色权限，只有会话存档相关的权限
                    if (in_array($node["permission_key"], ["session_archive", "session_search", "user_login", "user_config"])) {
                        $normalStaffUserRolePermissionList[] = $child["permission_key"];
                    }
                }
            }
        }


        //默认角色初始化写入
        $defaultRole = [EnumUserRoleType::NORMAL_STAFF->value, EnumUserRoleType::ADMIN->value, EnumUserRoleType::SUPPER_ADMIN->value];
        foreach ($defaultRole as $role_id) {
            $data = [
                "id" => $role_id,
                "permission_config" => $adminUserRolePermissionList
            ];

            if ($role_id == EnumUserRoleType::NORMAL_STAFF->value) {//普通员工角色
                $data["permission_config"] = $normalStaffUserRolePermissionList;
            }

            Yii::getNatsClient()->request('main.change_role_permission_config', json_encode($data), function (Payload $response) {

            });

        }


        //用户角色数据恢复
        $allUserRoleDetail = UserRoleDetailModel::query()->getAll();
        if (!empty($allUserRoleDetail)) {
            $corpStaffUserList = [];
            foreach ($allUserRoleDetail->toArray() as $item) {
                $arrKey = $item["corp_id"] . "|" . $item["other_role_id"];
                if (!array_key_exists($arrKey, $corpStaffUserList)) {
                    $corpStaffUserList[$arrKey] = [];
                }

                $corpStaffUserList[$arrKey][] = $item["staff_userid"];
            }

            if (!empty($corpStaffUserList)) {
                foreach ($corpStaffUserList as $key => $item) {
                    $arr = explode("|", $key);

                    $changeData = [
                        "corp_id" => $arr[0] ?? "",
                        "new_role_id" => $arr[1] ?? "",
                        "staff_userid" => $item,
                    ];

                    Yii::getNatsClient()->request('main.change_staff_role', json_encode($changeData), function (Payload $response) {

                    });
                }
            }
        }


        return;
    }

    public function getBroadcastRouters(): array
    {
        return [];
    }


    public function getMicroServiceRouters(): array
    {

        return [
            Micro::name('check_permission')->action(CheckMirco::class),
            Micro::name('get_role_list')->action(GetRoleListMirco::class),
        ];
    }

    public function getCronRouters(): array
    {
        return [
            // 验证模块有效期
            Cron::name('check-expire')->spec('* * * * *')
                ->action(CheckExpireCron::class, []),
        ];
    }
}
