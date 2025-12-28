<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Runner;

use Common\Exceptions\RetryableJobException;
use Common\Job\Producer;
use Common\Yii;
use Spiral\RoadRunner\Payload;
use Spiral\RoadRunner\Worker;
use Throwable;
use Yiisoft\Di\StateResetter;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Runner\ApplicationRunner;

use function gc_collect_cycles;
use function gc_mem_caches;
use function json_decode;

/**
 * NewJobsRunner - 对接自定义 jobs 插件的 Runner
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

        while (true) {
            $payload = $worker->waitPayload();
            if (empty($payload)) {
                break;
            }

            try {
                // 解析任务数据
                $requestData = json_decode($payload->body, true);

                if (!isset($requestData['handler']) || !isset($requestData['data'])) {
                    throw new \Exception('Invalid job payload format');
                }

                $handler = $requestData['handler'];
                $dataJson = $requestData['data'];
                $jobData = json_decode($dataJson, true);

                $className = $jobData['className'] ?? $handler;
                $args = unserialize($jobData['args'] ?? []);
                $logger->debug('Processing job', [
                    'handler' => $handler,
                    'className' => $className,
                    'args' => $args,
                ]);

                // 执行任务
                Producer::dispatchSync($className, $args);

                // 返回成功响应
                $worker->respond(new Payload('ok'));

            } catch (RetryableJobException $e) {
                // 可重试异常，重新入队
                $logger->error('Retryable job exception', [
                    'handler' => $handler ?? 'unknown',
                    'throwable' => $e,
                ]);

                // 重新入队
                if (isset($className) && isset($args)) {
                    Producer::dispatch($className, $args);
                }

                $worker->respond(new Payload('retry'));

            } catch (Throwable $e) {
                // 不可重试异常，记录日志
                $logger->error('Job execution failed', [
                    'handler' => $handler ?? 'unknown',
                    'throwable' => $e,
                ]);

                $worker->respond(new Payload('error: ' . $e->getMessage()));

            } finally {
                // 重置状态和清理内存
                $stateResetter->reset();
                gc_collect_cycles();
                gc_mem_caches();
            }
        }

        $application->shutdown(0);
    }
}
