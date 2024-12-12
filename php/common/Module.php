<?php

namespace Common;

use Exception;
use Spiral\RoadRunner\Services\Manager;

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

        $namespace = "Modules\\{$className}\\";
        $path = __DIR__ . "/../modules/" . $moduleName . "/backend";
        $composerLoader->addPsr4($namespace, $path);
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
    
    /**
    * 获取所有模块目录，并确保main模块在第一位
    *
    * @throws Exception
    */
    public static function getModuleDirectories(): array
    {
        $modulesPath = Yii::aliases()->get("@modules");
        $dirs = array_map('basename', glob($modulesPath . '/*', GLOB_ONLYDIR));

        if (!in_array('main', $dirs)) {
            throw new Exception('main模块不存在');
        }

        // main模块排到最前面
        usort($dirs, fn ($a, $b) => ($a === 'main') ? -1 : (($b === 'main') ? 1 : strcasecmp($a, $b)));

        return $dirs;
    }
    
    public static function getModuleConfigCacheKey(string $moduleName)
    {
        return "module_{$moduleName}_config";
    }
    
    public static function getModuleRunningCacheKey(string $moduleName)
    {
        return "module_{$moduleName}_paused_status";
    }
    
    public static function getModuleHttpPortCacheKey($moduleName)
    {
        return "module_{$moduleName}_http_port";
    }
    
    public static function getModuleRpcPortCacheKey($moduleName)
    {
        return "module_{$moduleName}_rpc_port";
    }
    
    /**
     * 获取指定模块的配置信息
     */
    public static function getModuleConfig(string $moduleName): array
    {
        $result = Yii::cache()->psr()->get(self::getModuleConfigCacheKey($moduleName));
        $result['paused'] = Yii::cache()->psr()->get(self::getModuleRunningCacheKey($moduleName));
        $result['http_port'] = Yii::cache()->psr()->get(self::getModuleHttpPortCacheKey($moduleName));
        $result['rpc_port'] = Yii::cache()->psr()->get(self::getModuleRpcPortCacheKey($moduleName));
        
        return $result;
    }
    
    /**
    * 启动模块
    */
    public static function enable(string $name, array $moduleConfig, $rpcPort, $httpPort): void
    {
        $env = [
            // 版本信息
            'MODULE_NAME' => "$name",
            'MODULE_VERSION' => $moduleConfig['version'],
            'MODULE_DESCRIPTION' => $moduleConfig['description'],

            // rpc
            'MODULE_RPC_PORT' => $rpcPort,

            // http
            'MODULE_HTTP_PORT' => $httpPort,
            'MODULE_STATIC_DIR' => $moduleConfig['public_dir'],
            
            // 数据库
            'DB_HOST' => $_ENV['DB_HOST'],
            'DB_PORT' => $_ENV['DB_PORT'],
            'DB_DATABASE' => $_ENV['DB_DATABASE'],
            'DB_USERNAME' => $name,
            'Db_PASSWORD' => $name,
            
            // 消费者配置
            ...$moduleConfig['consumer_route_list'],
        ];
        
        (new Manager(Yii::getDefaultRpcClient()))->create(
            name: $name,
            serviceNameInLogs: true,
            command: "golang/build/app {$name}",
            remainAfterExit: true,
            env: $env,
            processNum: 1,
            restartSec: 5,
        );
        
        Yii::cache()->psr()->setMultiple([
            self::getModuleRunningCacheKey($name) => false,
            self::getModuleHttpPortCacheKey($name) => $httpPort,
            self::getModuleRpcPortCacheKey($name) => $rpcPort,
        ]);
    }
    
    /**
     * 禁用模块
     */
    public static function disable(string $name)
    {
        (new Manager(Yii::getDefaultRpcClient()))->terminate($name);
       
        $cacheKey = self::getModuleRunningCacheKey($name);
        Yii::cache()->psr()->set($cacheKey, true);
        
        Yii::cache()->psr()->deleteMultiple([
            self::getModuleHttpPortCacheKey($name),
            self::getModuleRpcPortCacheKey($name),
        ]);
    }
    
    private static function findAvailablePorts(int $start, int $end, int $count): array
    {
        $ports = [];

        for ($port = $start; $port <= $end && count($ports) < $count; $port++) {
            if (self::isPortAvailable($port)) {
                $ports[] = $port;
            }
        }

        if (count($ports) < $count) {
            throw new Exception("仅找到 {$count} 个可用端口，但需要 {$count} 个。");
        }

        return $ports;
    }

    public static function isPortAvailable(int $port, int $timeout = 1): bool
    {
        $connection = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
        if ($connection) {
            fclose($connection);
            return true; // 端口未被占用
        }
        
        return false; // 端口被占用
    }

    public static function findAvailableRpcPorts(int $count): array
    {
        return self::findAvailablePorts(6002, 6100, $count);
    }

    public static function findAvailableHttpPorts(int $count): array
    {
        return self::findAvailablePorts(8081, 8100, $count);
    }
}
