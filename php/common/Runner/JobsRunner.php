<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Runner;

use Common\Exceptions\RetryableJobException;
use Common\Job\Producer;
use Common\Yii;
use function gc_collect_cycles;
use function gc_mem_caches;
use Spiral\RoadRunner\Jobs\Task\Factory\ReceivedTaskFactory;
use Spiral\RoadRunner\Worker;
use Throwable;
use Yiisoft\Di\StateResetter;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Runner\ApplicationRunner;

/**
 * `RoadRunnerHttpApplicationRunner` runs the Yii Jobs application using RoadRunner.
 */
final class JobsRunner extends ApplicationRunner
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
        $receivedTaskFactory = new ReceivedTaskFactory($worker);

        while (true) {
            $payload = $worker->waitPayload();
            if (empty($payload)) {
                break;
            }
            $task = $receivedTaskFactory->create($payload);

            $id = $task->getId();
            $name = $task->getName();
            $payload = $task->getPayload();
            $data = unserialize($payload);

            // 延迟任务
            $deferredExecTime = (int) $task->getHeaderLine('deferred_exec_time');
            if ($deferredExecTime > 0 && $deferredExecTime >= time()) {
                $time = date("Y-m-d H:i:s", $deferredExecTime);
                $task->withDelay(max($deferredExecTime - time() + 1, 0))->nack("任务延迟到{$time}执行", true);

                continue;
            }

            try {
                Producer::dispatchSync($name, $data);
            } catch (RetryableJobException $e) {
                Producer::dispatch($name, $data, $e->getDelay());
                $logger->error($e, [
                    'id' => $task->getId(),
                    'headers' => $task->getHeaders(),
                    'name' => $task->getName(),
                    'payload' => $task->getPayload(),
                    'throwable' => $e,
                ]);
            } catch (Throwable $e) {
                $logger->error($e, [
                    'id' => $task->getId(),
                    'headers' => $task->getHeaders(),
                    'name' => $task->getName(),
                    'payload' => $task->getPayload(),
                    'throwable' => $e,
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
