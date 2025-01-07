<?php

namespace Common\Runner;

use Common\Yii;
use Spiral\RoadRunner\Payload;
use Throwable;
use Yiisoft\Di\StateResetter;
use Yiisoft\Yii\Console\Application;
use Yiisoft\Yii\Runner\ApplicationRunner;
use Spiral\RoadRunner\Worker;

class MicroRunner extends ApplicationRunner
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

    public function run(): void
    {
        $container = $this->getContainer();

        $this->runBootstrap();
        $this->checkEvents();

        $application = $container->get(Application::class);
        $application->start();

        $stateResetter = $container->get(StateResetter::class);

        $worker = Worker::create();
        while ($payload = $worker->waitPayload()) {
            try {
                $payload = json_decode($payload->body, true);
                $handler = $payload['handler'];
                $data = $payload['data'];
                $result = (new $handler($data))->handle();
                $worker->respond(new Payload(json_encode($result)));
            } catch (Throwable $e) {
                $worker->respond(new Payload(json_encode(['error' => $e->getMessage()])));
                Yii::logger()->error($e);
            } finally {
                $stateResetter->reset();
                gc_collect_cycles();
                gc_mem_caches();
            }
        }

        $application->shutdown(0);
    }
}
