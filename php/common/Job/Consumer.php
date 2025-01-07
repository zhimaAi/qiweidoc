<?php

namespace Common\Job;

use Common\Module;
use Common\Yii;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Queue\NatsCreateInfo;

class Consumer
{
    private string $queueName;

    private int $count = 1;

    private string $handler;

    private bool $deleteOnStop = true;

    public static function name(string $name): Consumer
    {
        $route = new self();
        $route->queueName = $name;

        return $route;
    }

    public function count(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function reserveOnStop(): self
    {
        $this->deleteOnStop = false;

        return $this;
    }

    public function action(string $className): self
    {
        $this->handler = $className;

        return $this;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function register()
    {
        $moduleName = Module::getCurrentModuleName();
        $jobs = new Jobs(Yii::getRpcClient());

        $name = "{$moduleName}_{$this->queueName}";
        $jobs->create(new NatsCreateInfo(
            name: $name,
            subject: $name,
            stream: $name,
            priority: 10,
            prefetch: $this->count,
            deleteStreamOnStop: $this->deleteOnStop,
            deleteAfterAck: true,
        ))->resume();
    }
}
