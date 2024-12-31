<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Runner;

use Common\Yii;
use function gc_collect_cycles;
use function gc_mem_caches;
use Spiral\RoadRunner\Tcp\TcpEvent;
use Spiral\RoadRunner\Tcp\TcpWorker;
use Spiral\RoadRunner\Worker;
use Throwable;
use Yiisoft\Di\StateResetter;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Runner\ApplicationRunner;

/**
 * `RoadRunnerHttpApplicationRunner` runs the Yii Jobs application using RoadRunner.
 */
final class RoadRunnerTcpApplicationRunner extends ApplicationRunner
{
    public function __construct(
        string  $rootPath,
        bool    $debug = false,
        bool    $checkEvents = false,
        ?string $environment = null,
        string  $bootstrapGroup = 'bootstrap-console',
        string  $eventsGroup = 'events-console',
        string  $diGroup = 'di-console',
        string  $diProvidersGroup = 'di-providers-console',
        string  $diDelegatesGroup = 'di-delegates-console',
        string  $diTagsGroup = 'di-tags-console',
        string  $paramsGroup = 'params-console',
        array   $nestedParamsGroups = ['params'],
        array   $nestedEventsGroups = ['events'],
        array   $configModifiers = [],
        string  $configDirectory = 'config',
        string  $vendorDirectory = 'vendor',
        string  $configMergePlanFile = '.merge-plan.php',
    ) {
        parent::__construct(
            $rootPath,
            $debug,
            $checkEvents,
            $environment,
            $bootstrapGroup,
            $eventsGroup,
            $diGroup,
            $diProvidersGroup,
            $diDelegatesGroup,
            $diTagsGroup,
            $paramsGroup,
            $nestedParamsGroups,
            $nestedEventsGroups,
            $configModifiers,
            $configDirectory,
            $vendorDirectory,
            $configMergePlanFile,
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws Throwable
     */
    public function run(): void
    {
        $container = $this->getContainer();

        $this->runBootstrap();
        $this->checkEvents();

        $application = $container->get(Application::class);
        $application->start();

        $stateResetter = $container->get(StateResetter::class);
        $logger = Yii::logger();

        $worker = Worker::create();
        $tcpWorker = new TcpWorker($worker);
        while ($request = $tcpWorker->waitRequest()) {
            try {
                if ($request->event === TcpEvent::Connected) {
                    if ($request->remoteAddr !== '127.0.0.1') {
                        $tcpWorker->close();
                        continue;
                    }
                    $tcpWorker->respond("200 ok \r\n");
                } elseif ($request->event === TcpEvent::Data) {
                    $body = $request->body;
                    $tcpWorker->respond(\json_encode([
                        'remote_addr' => $request->remoteAddr,
                        'server' => $request->server,
                        'uuid' => $request->connectionUuid,
                        'body' => $request->body,
                        'event' => $request->event,
                    ]));
                } elseif ($request->event === TcpEvent::Close) {
                    $logger->info("tcp closed");
                }
            } catch (Throwable $e) {
                $logger->error($e, [
                    'body' => $request->getBody(),
                    'remove_address' => $request->getRemoteAddress(),
                    'event' => $request->getEvent(),
                    'uuid' => $request->getConnectionUuid(),
                    'server' => $request->getServer(),
                ]);
            } finally {
                $stateResetter->reset();
                gc_collect_cycles();
                gc_mem_caches();
            }
        }

        $application->shutdown(0);
    }
}
