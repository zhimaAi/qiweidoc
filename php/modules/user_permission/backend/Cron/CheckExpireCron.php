<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\UserPermission\Cron;

use Common\HttpClient;
use Common\Module;
use Common\Yii;
use Modules\Main\Model\CorpModel;
use Yiisoft\Security\Crypt;

/**
 * @author rand
 * @ClassName StatisticsHintConsumer
 * @date 2024/12/611:26
 * @description 单聊统计
 */
class CheckExpireCron
{
    public function __construct()
    {
    }

    public function handle()
    {
        $moduleName = Module::getCurrentModuleName();
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return;
        }

        try {
            $baseUri = Yii::params()['module-host'];
            $uri = "/wkOperation/open-module/get-usage-detail";
            $body = ['corp_id' => $corp->get('id'), 'module_name' => $moduleName];

            $res = (new HttpClient(['base_uri' => $baseUri]))->post($uri, $body);
            $responseData = json_decode($res->getBody(), true);
            $str = $responseData['data'] ?? "";
            if (empty($str)) {
                throw new \Exception("接口请求失败");
            }
            $result = (new Crypt)->decryptByKey(base64_decode($str), "zhima888");
            $checkRes = json_decode($result, true);
            if ($checkRes['status'] == 1) {
                echo "未过期";
            } else {
                Module::stopModule($moduleName);
                echo "已过期";
            }
        } catch (\Exception $e) {
            ddump($e->getMessage());
            ddump($e->getTraceAsString());
        }
    }
}
