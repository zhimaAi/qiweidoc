<?php

namespace Common\Command;

use Basis\Nats\Message\Payload;
use Basis\Nats\Service\Service;
use Common\Module;
use Common\Yii;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Router\RouteCollectionInterface;

#[AsCommand(name: 'micro-service', description: '测试命令', hidden: false)]
class MicroServiceCommand extends Command
{
    private RouteCollectionInterface $routeCollection;

    public function __construct(RouteCollectionInterface $routeCollection, ?string $name = null)
    {
        $this->routeCollection = $routeCollection;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);

        $serviceName = Module::getCurrentModuleName();
        $micro = new Service(Yii::getNatsClient(), Module::getCurrentModuleName());
        $group = $micro->addGroup(Module::getCurrentModuleName());
        $routers = Module::getRouterProvider()->getMicroServiceRouters();
        foreach ($routers as $name => $handler) {
            $group->addEndpoint($name, $handler);
        }
        $micro->client->logger->info("{$serviceName} is ready to accept connections\n");
        while (true) {
            try {
                $micro->client->process();
            } catch (\Exception $e) {
                $micro->client->logger->error("{$serviceName} encountered an error:\n" . $e->getMessage() . "\n");
            } finally {
                gc_collect_cycles();
                gc_mem_caches();
                pcntl_signal_dispatch();
            }
        }
    }

    public function signalHandler($signal)
    {
        switch ($signal) {
            default:
                exit;
        }
    }
}
