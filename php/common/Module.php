<?php

namespace Common;

class Module
{
    private static string $module;

    public static function setModule()
    {
        self::$module = getenv('MODULE_NAME') ?: 'main';
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
}
