<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Module;
use Common\Yii;
use LogicException;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\StaffService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use ZipArchive;

class ModuleController extends BaseController
{
    /**
     * 获取模块列表
     */
    public function getModuleList(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        // 先从芝麻微客获取最新的模块列表
        $remoteModuleList = Module::getAllRemoteModuleConfigList($corp->get('id'));

        // 获取本地已安装的模块
        $result = Module::getAllLocalModuleConfigList();
        $localModuleList = array_values(array_filter($result, function ($item) {
            return ($item['name'] ?? '') != 'main';
        }));

        // 获取main模块信息
        $localMainModule = array_values(array_filter($result, function ($item) {
            return ($item['name'] ?? '') == 'main';
        }));
        $localMainModule = $localMainModule[0];

        // 根据远程配置的order进行排序
        $remoteModuleOrderList = array_column($remoteModuleList, 'order', 'name');
        foreach ($localModuleList as &$module) {
            if (isset($module['name'])) {
                $module['order'] = $remoteModuleOrderList[$module['name']] ?? 0;
            } else {
                $module['order'] = 0;
            }
        }
        usort($localModuleList, fn ($a, $b) => $a['order'] - $b['order']);

        return $this->jsonResponse(['main_local_module' => $localMainModule, 'remote_module_list' => $remoteModuleList, 'local_module_list' => $localModuleList]);
    }

    /**
     * 获取模块详情
     */
    public function getModuleDetail(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        // 获取本地模块信息
        $localModule = Module::getLocalModuleConfig($name);

        // 获取主模块信息
        $mainModule = Module::getLocalModuleConfig('main');

        // 获取远程模块信息
        $remoteModule = Module::getRemoteModuleDetail($name, $corp->get('id'));

        // 收集浏览记录
        Module::collectModuleView($name, $corp->get('id'));

        return $this->jsonResponse(['remote_module' => $remoteModule, 'local_module' => $localModule, 'main_local' => $mainModule]);
    }

    /**
     * 启用模块
     */
    public function enableModule(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        $key = "mutex_enable_module_{$name}";
        Yii::mutex(10)->acquire($key);

        try {
            $corp = $request->getAttribute(CorpModel::class);
            Module::startModule($name);
            Module::addTryRecordInRemote($name, $corp->get('id'));
        } finally {
            Yii::mutex()->release($key);
        }

        return $this->jsonResponse();
    }

    /**
     * 禁用模块
     */
    public function disableModule(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $key = "mutex_disable_module_{$name}";
        Yii::mutex(10)->acquire($key);

        try {
            Module::stopModule($name);
        } finally {
            Yii::mutex()->release($key);
        }

        // 还原设置的会话存档员工数量
        if ($name == 'archive_staff') {
            StaffService::checkStaffEnableArchive($corp);
        }

        return $this->jsonResponse();
    }

    /**
     * 安装/更新模块
     */
    public function installModule(ServerRequestInterface $request, #[RouteArgument('name')] string $name): ResponseInterface
    {
        if ($name == 'main') {
            throw new LogicException("不能安装主应用");
        }

        // 防止重复安装
        $key = "mutex_install_module_{$name}";
        Yii::mutex(10)->acquire($key);

        try {
            $this->_installModule($request->getAttribute(CorpModel::class), $name);
        } finally {
            Yii::mutex()->release($key);
        }

        return $this->jsonResponse();
    }

    private function _installModule(CorpModel $corp, string $name): void
    {
        // 获取最新版本
        $data = Module::getRemoteLatestModuleVersion($name, $corp->get('id'));

        // 检查该企业是否购买
        $downloadUrl = $data['download_url'] ?? '';
        if (empty($downloadUrl)) {
            throw new LogicException('请购买或续费');
        }

        // 检测是否兼容
        $compatibleMainVersionList = json_decode($data['compatible_main_version_list'] ?? "[]", true);
        if (empty($compatibleMainVersionList)) {
            throw new LogicException("没有找到对应的模块版本代码");
        }

        // 尝试获取本地已安装模块信息
        $restart = false;
        try {
            $localModule = Module::getLocalModuleConfig($name);
            if (!empty($localModule) && $localModule['version'] == $data['version']) {
                Yii::logger()->warning("已经安装过该版本了");
                return;
            }

            if (!empty($localModule) && !empty($localModule['paused'])) {
                $restart = true;
            }
        } catch (Throwable) {}

        // 获取main模块信息
        $mainModuleConfig = Module::getLocalModuleConfig('main');
        $mainModuleVersion = $mainModuleConfig['version'] ?? 0;
        if (!is_compatible_version($mainModuleVersion, $compatibleMainVersionList)) {
            throw new LogicException("与主应用版本不兼容");
        }

        // 下载压缩包
        $moduleCode = '/tmp/module.zip';
        $content = file_get_contents($downloadUrl);
        file_put_contents($moduleCode, $content);

        // 打开压缩包
        $zip = new ZipArchive;
        if ($zip->open($moduleCode) === false) {
            throw new LogicException("读取代码失败");
        }

        // 解压缩到临时目录
        $filesystem = new Filesystem();
        $tmpModule = "/tmp/{$name}";
        $filesystem->remove($tmpModule);
        $filesystem->mkdir($tmpModule);
        if ($zip->extractTo($tmpModule) === false) {
            throw new LogicException("解压缩代码失败");
        }
        $zip->close();

        // 移动到模块目录
        $target = Yii::aliases()->get('@modules') . "/{$name}";
        $filesystem->rename($tmpModule, $target, true);

        // 关掉模块
        try {
            Module::stopModule($name);
            if (!$restart) {
                Module::startModule($name);
            }
        } catch (Throwable $e) {
            $filesystem->remove($target);
            throw new LogicException($e->getMessage());
        }

        // 添加试用记录
        Module::addTryRecordInRemote($name, $corp->get('id'));
    }
}
