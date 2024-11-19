<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core;

use App\Libraries\Core\Consumer\ConsumerInterface;
use ErrorException;
use function gc_collect_cycles;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Di\NotFoundException;
use Yiisoft\Di\StateResetter;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Runner\ApplicationRunner;

/**
 * `RoadRunnerHttpApplicationRunner` runs the Yii Jobs application using RoadRunner.
 */
final class RoadRunnerJobsApplicationRunner extends ApplicationRunner
{
    public function __construct(
        string $rootPath,
        bool $debug = false,
        bool $checkEvents = false,
        ?string $environment = null,
        string $bootstrapGroup = 'bootstrap-console',
        string $eventsGroup = 'events-console',
        string $diGroup = 'di-console',
        string $diProvidersGroup = 'di-providers-console',
        string $diDelegatesGroup = 'di-delegates-console',
        string $diTagsGroup = 'di-tags-console',
        string $paramsGroup = 'params-console',
        array $nestedParamsGroups = ['params'],
        array $nestedEventsGroups = ['events'],
        array $configModifiers = [],
        string $configDirectory = 'config',
        string $vendorDirectory = 'vendor',
        string $configMergePlanFile = '.merge-plan.php',
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
     * @throws CircularReferenceException|ErrorException|InvalidConfigException|JsonException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     */
    public function run(): void
    {
        $container = $this->getContainer();

        $this->runBootstrap();
        $this->checkEvents();

        $application = $container->get(Application::class);
        $application->start();

        $consumer = new Consumer();
        $stateResetter = $container->get(StateResetter::class);

        $logger = Yii::logger('jobs');

        while ($task = $consumer->waitTask()) {
            try {
                $processor = $this->resolveTaskProcessor($container, $task);
                $processor->handle();

                $task->ack();
            } catch (\Throwable $e) {
                $this->handleTaskFailure($task, $e);
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

    private function resolveTaskProcessor(ContainerInterface $container, ReceivedTaskInterface $task): ConsumerInterface
    {
        $name = $task->getName();
        $payload = $task->getPayload();
        $data = (new SmartSerializer())->unserialize($payload);

        return $container->get(ConsumerInterface::class)($name, $data);
    }

    private function handleTaskFailure(ReceivedTaskInterface $task, \Throwable $e): void
    {
        $attempts = (int) $task->getHeaderLine('attempts');
        $retryDelay = max((int) $task->getHeaderLine('retry-delay'), 3);

        if ($attempts > 0) {
            $task->withHeader('attempts', (string) ($attempts - 1))
                ->withHeader('retry-delay', (string) ($retryDelay * 2))
                ->withDelay($retryDelay * 2)
                ->requeue($e);
        } else {
            $task->ack();
        }
    }
}
