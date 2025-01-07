<?php

namespace Common\Command;

use Common\Module;
use Common\Yii;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Edl\Encryptor;
use Edl\FileEncryptSystem;
use ZipArchive;

#[AsCommand(name: 'pack-module', description: '模块打包', hidden: false)]
class PackModuleCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('module', InputArgument::REQUIRED, '模块名称');
        $this->addOption('encrypt', 'e', InputArgument::REQUIRED, '是否加密');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $modulePath = Yii::aliases()->get('@modules') . "/" . $input->getArgument('module');
        if (!is_dir($modulePath)) {
            throw new LogicException("模块不存在");
        }
        if (!file_exists($modulePath . "/public/build/index.html")) {
            throw new LogicException("模块的前端代码未打包");
        }
        $moduleConfig = Module::getLocalModuleConfig($input->getArgument('module'));
        if (empty($moduleConfig)) {
            throw new LogicException("获取模块配置信息失败");
        }

        // 创建压缩包
        $zip = new ZipArchive();
        $zipFileName = sprintf("%s/%s-%s.zip", Yii::aliases()->get('@root'), $moduleConfig['name'], $moduleConfig['version']);
        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new LogicException("无法创建压缩文件: {$zipFileName}");
        }

        if ($input->getOption('encrypt')) { // 需要加密
            if (!file_exists($modulePath . "/private.key") || !file_exists($modulePath . "/public.key")) {
                Encryptor::generateKeyPair($modulePath . "/public.key", $modulePath . "/private.key");
            }
            @unlink("/tmp/backend.phpe");
            (new FileEncryptSystem($modulePath . "/private.key", $modulePath . "/public.key"))->encryptDirectory($modulePath . '/backend',  "/tmp/backend.phpe");
            if (file_exists("/tmp/backend.phpe")) {
                $zip->addFile("/tmp/backend.phpe", "backend.phpe");
            }
            $zip->addFile($modulePath . "/private.key", 'private.key');
        } else { // 无需加密
            $this->addFilesToZip($zip, $modulePath . "/backend", "backend");
        }

        // 把其它文件添加到压缩包中
        $zip->addFile($modulePath . '/yii', 'yii');
        $this->addFilesToZip($zip, $modulePath . "/migration", "migration");
        $this->addFilesToZip($zip, $modulePath . "/public", "public");
        if (file_exists($modulePath . "/manifest.json")) {
            $zip->addFile($modulePath . "/manifest.json", "manifest.json");
        }
        $zip->close();

        return ExitCode::OK;
    }

    private function addFilesToZip(ZipArchive $zip, string $sourcePath, string $zipPath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourcePath) + 1);
                $zip->addFile($filePath, $zipPath . '/' . $relativePath);
            }
        }
    }
}
