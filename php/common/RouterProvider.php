<?php

namespace Common;

abstract class RouterProvider
{
    public function getManifest(): array
    {
        $modulesPath = Yii::aliases()->get("@modules");
        $currentModule = Module::getCurrentModuleName();
        $content = file_get_contents("{$modulesPath}/{$currentModule}/manifest.json");

        return json_decode($content, true);
    }

    public function getPublicDirectory(): string
    {
        $modulesPath = Yii::aliases()->get("@modules");
        $currentModule = Module::getCurrentModuleName();

        return $modulesPath . "/{$currentModule}/public/";
    }

    public function getProtoFileList(): array
    {
        $modulesPath = Yii::aliases()->get("@modules");
        $currentModule = Module::getCurrentModuleName();

        return glob("{$modulesPath}/{$currentModule}/proto/*.proto");
    }

    abstract public function getHttpRouters(): array;

    abstract public function getGrpcRouters(): array;

    abstract public function getConsoleRouters(): array;

    abstract public function getConsumerRouters(): array;
}
