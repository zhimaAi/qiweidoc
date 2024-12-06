<?php

namespace Common\Command;

use Common\Module;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'get-current-module-info', description: '获取当前模块信息')]
class GetCurrentModuleInfo extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $provider = Module::getRouterProvider();

        $result = $provider->getManifest();
        $result['consumer_route_list'] = [];
        $consumerRouters = $provider->getConsumerRouters();
        foreach ($consumerRouters as $router) {
            $result['consumer_route_list'][] = [
                'name' => $router->getQueueName(),
                'count' => $router->getCount(),
                'delete_on_stop' => $router->getDeleteOnStop(),
            ];
        }
        $result['public_dir'] = $provider->getPublicDirectory();
        $result['proto_file_list'] = $provider->getProtoFileList();

        $output->write(json_encode($result));

        return ExitCode::OK;
    }
}
