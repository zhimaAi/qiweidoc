<?php

namespace Common\Job;

class Consumer
{
    private string $queueName;

    private int $count = 1;

    private string $className;

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
        $this->className = $className;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getDeleteOnStop(): bool
    {
        return $this->deleteOnStop;
    }
}
