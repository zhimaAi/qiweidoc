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
    const LOCK_TIME = 3600 * 24;

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

            // 相当于是一个心跳功能，会重复推送任务
            // 加锁防止任务重复执行
            // 如果任务执行过程中进程挂了，锁会自动释放，然后重复推送可以保证任务再次执行
            $lockKey = "task_lock:{$id}";
            if (!Yii::mutex(self::LOCK_TIME)->acquire($lockKey)) {
                dump("有任务执行时间过长", compact('id', 'name'));
                if ($this->debug) {
                    $worker->stop();
                    break;
                } else {
                    $task->withDelay(30)->nack("任务正在执行中", true);
                    continue;
                }
            }

            try {
                Producer::dispatchSync($name, $data);
                $task->ack();
            } catch (RetryableJobException $e) {
                $task->withDelay($e->getDelay())->nack($e, true);
                $logger->error($e, [
                    'id' => $task->getId(),
                    'headers' => $task->getHeaders(),
                    'name' => $task->getName(),
                    'payload' => $task->getPayload(),
                    'throwable' => $e,
                ]);
            } catch (Throwable $e) {
                $task->ack();
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
