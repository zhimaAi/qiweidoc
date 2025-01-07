<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common;

use Basis\Nats\Message\Payload;
use Exception;
use LogicException;
use Edl\FileEncryptSystem;
use Throwable;

class Module
{
    private static string $module = '';

    public static function setModule($module)
    {
        self::$module = $module;
    }

    public static function getCurrentModuleName(): string
    {
        return self::$module;
    }

    public static function loadModule($composerLoader, string $moduleName)
    {
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($moduleName))));

        $path = __DIR__ . "/../modules/" . $moduleName . "/backend";
        $encryptedCode = __DIR__ . "/../modules/" . $moduleName . "/backend.phpe";
        $privateKey = __DIR__ . "/../modules/" . $moduleName . "/private.key";

        if (file_exists($encryptedCode) && file_exists($privateKey)) {
            (new FileEncryptSystem($privateKey))->decryptAndLoadPhpFiles($encryptedCode);
        } elseif (is_dir($path)) {
            $namespace = "Modules\\{$className}\\";
            $composerLoader->addPsr4($namespace, $path);
        } else {
            throw new Exception("模块{$moduleName}加载失败");
        }
    }

    public static function getDynamicClassByModule(string $subClass): string
    {
        $moduleName = self::getCurrentModuleName();
        $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($moduleName))));

        return "\Modules\\{$className}\\{$subClass}";
    }

    public static function getRouterProvider(): RouterProvider
    {
        $class = Module::getDynamicClassByModule("Routes");

        return new $class();
    }

    public static function getAllLocalModuleConfigList(): array
    {
        $host = Yii::getModuleManageAddress();
        $uri = "/internal/modules/list";
        $response = (new HttpClient(['base_uri' => $host]))->post($uri);
        if ($response->getStatusCode() != 200) {
            throw new LogicException(json_decode($response->getBody(), true)['message'] ?? "未知错误");
        }
        return json_decode($response->getBody(), true);
    }

    public static function getLocalModuleConfig(string $name): array
    {
        $host = Yii::getModuleManageAddress();
        $uri = "/internal/modules/info";
        $response = (new HttpClient(['base_uri' => $host]))->post($uri, ['name' => $name]);
        if ($response->getStatusCode() != 200) {
            throw new LogicException(json_decode($response->getBody(), true)['message'] ?? "未知错误");
        }
        return json_decode($response->getBody(), true);
    }

    public static function startModule(string $name): void
    {
        $host = Yii::getModuleManageAddress();
        $uri = "/internal/modules/start";
        $result = (new HttpClient(['base_uri' => $host]))->post($uri, ['name' => $name]);
        if ($result->getStatusCode() != 200) {
            throw new LogicException(json_decode($result->getBody(), true)['message'] ?? "未知错误");
        }
    }

    public static function stopModule(string $name): void
    {
        Yii::getNatsClient()->request("{$name}.stop-module", '', function (Payload $payload) {});

        $host = Yii::getModuleManageAddress();
        $uri = "/internal/modules/stop";
        $result = (new HttpClient(['base_uri' => $host]))->post($uri, ['name' => $name]);
        if ($result->getStatusCode() != 200) {
            throw new LogicException(json_decode($result->getBody(), true)['message'] ?? "未知错误");
        }
    }

    public static function getAllRemoteModuleConfigList(string $corpId)
    {
        $host = Yii::params()['module_host'];
        $uri = '/wkOperation/open-module/list';
        $query = ['size' => 100, 'corp_id' => $corpId];
        $response = (new HttpClient(['base_uri' => $host]))->get($uri, $query);
        return json_decode($response->getBody(), true)['data']['list'] ?? [];
    }

    public static function getRemoteModuleDetail(string $moduleName, string $corpId)
    {
        $host = Yii::params()['module_host'];
        $uri = '/wkOperation/open-module/detail';
        $query = ['name' => $moduleName, 'corp_id' => $corpId];
        $response = (new HttpClient(['base_uri' => $host]))->get($uri, $query);
        return json_decode($response->getBody(), true)['data'] ?? [];
    }

    public static function getRemoteLatestModuleVersion(string $moduleName, string $corpId)
    {
        $baseUri = Yii::params()['module_host'];
        $uri = '/wkOperation/open-module/get-module-latest-version';
        $query = ['module_name' => $moduleName, 'corp_id' => $corpId];
        $response = (new HttpClient(['base_uri' => $baseUri]))->get($uri, $query);
        $data = json_decode($response->getBody(), true)['data'] ?? [];
        if (empty($data)) {
            throw new LogicException("没有找到对应的模块版本");
        }
        return $data;
    }

    public static function addTryRecordInRemote(string $moduleName, string $corpId): void
    {
        $baseUri = Yii::params()['module_host'];
        $uri = "/wkOperation/open-module/create-try-order";
        $body = ['corp_id' => $corpId, 'module_name' => $moduleName];
        try {
            $res = (new HttpClient(['base_uri' => $baseUri]))->post($uri, $body);
            Yii::logger()->debug((string)$res->getBody());
        } catch (Throwable $e) {
            Yii::logger()->warning($e);
        }
    }

    public static function collectModuleInfo(string $moduleName, string $moduleVersion, string $corpId)
    {
        $data = [
            'url' => Yii::params()['module_host'] . '/wkOperation/open-module/collect-module-info',
            'name' => $moduleName,
            'version' => $moduleVersion,
            'corp_id' => $corpId,
        ];
        Yii::getRpcClient()->call('common.CronCollectModule', $data);
    }
}
