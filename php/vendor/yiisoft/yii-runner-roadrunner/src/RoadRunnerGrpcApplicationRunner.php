<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\RoadRunner;

use Spiral\RoadRunner\GRPC\Invoker;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\Worker;
use Yiisoft\Yii\Runner\ApplicationRunner;

/**
 * `RoadRunnerGrpcApplicationRunner` runs the Yii gRPC application using RoadRunner.
 */
final class RoadRunnerGrpcApplicationRunner extends ApplicationRunner
{
    private ?InvokerInterface $invoker = null;
    private array $services = [];
    private ?Worker $worker = null;

    /**
     * @param string $rootPath The absolute path to the project root.
     * @param bool $debug Whether the debug mode is enabled.
     * @param bool $checkEvents Whether to check events' configuration.
     * @param string|null $environment The environment name.
     * @param string $bootstrapGroup The bootstrap configuration group name.
     * @param string $eventsGroup The events' configuration group name.
     * @param string $diGroup The container definitions' configuration group name.
     * @param string $diProvidersGroup The container providers' configuration group name.
     * @param string $diDelegatesGroup The container delegates' configuration group name.
     * @param string $diTagsGroup The container tags' configuration group name.
     * @param string $paramsGroup The configuration parameters group name.
     * @param array $nestedParamsGroups Configuration group names that are included into configuration parameters group.
     * This is needed for recursive merging of parameters.
     * @param array $nestedEventsGroups Configuration group names that are included into events' configuration group.
     * This is needed for reverse and recursive merge of events' configurations.
     *
     * @psalm-param list<string> $nestedParamsGroups
     * @psalm-param list<string> $nestedEventsGroups
     */
    public function __construct(
        string $rootPath,
        bool $debug = false,
        bool $checkEvents = false,
        ?string $environment = null,
        string $bootstrapGroup = 'bootstrap-web',
        string $eventsGroup = 'events-web',
        string $diGroup = 'di-web',
        string $diProvidersGroup = 'di-providers-web',
        string $diDelegatesGroup = 'di-delegates-web',
        string $diTagsGroup = 'di-tags-web',
        string $paramsGroup = 'params-web',
        array $nestedParamsGroups = ['params'],
        array $nestedEventsGroups = ['events'],
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
        );
    }

    public function run(): void
    {
        $server = new Server($this->getInvoker(), ['debug' => $this->debug]);

        /**
         * @var class-string<ServiceInterface> $interface
         * @var ServiceInterface $service
         */
        foreach ($this->getServices() as $interface => $service) {
            $server->registerService($interface, new $service());
        }

        $server->serve($this->getWorker(), static function () {
            gc_collect_cycles();
        });
    }

    /**
     * Returns a new instance with the specified gRPC worker instance
     *
     * @return $this
     */
    public function withWorker(Worker $worker): self
    {
        $instance = clone $this;
        $instance->worker = $worker;

        return $instance;
    }

    /**
     * Transmitted services for registration gRPC server
     *
     * @param array $services Services array (key-value pairs)
     * ```php
     * [
     *      ServiceInterface::class => Service::class
     * ]
     * ```
     *
     * @return $this
     */
    public function setServices(array $services): self
    {
        $this->services = $services;

        return $this;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getWorker(): Worker
    {
        return $this->worker ?? Worker::create();
    }

    public function getInvoker(): InvokerInterface
    {
        return $this->invoker ?? new Invoker();
    }
}
