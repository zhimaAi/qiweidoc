<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Consumer;

use App\Libraries\Core\Yii;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;
use Throwable;

abstract class BaseConsumer implements ConsumerInterface
{
    protected string $queue = 'default'; // 队列名称

    protected int $attempts = 1; // 失败后重试次数

    protected array $context = [];

    /**
     * 异步执行
     * @throws Throwable
     */
    public static function dispatch(array $data, int $delay = 0): QueuedTaskInterface
    {
        /** @var static $instance */
        $instance = self::getInstance($data);

        $queue = Yii::queue($instance->queue);
        $task = $queue->create(static::class, serialize($data))
            ->withHeader('attempts', $instance->attempts)
            ->withDelay($delay);

        return $queue->dispatch($task);
    }

    /**
     * 同步执行
     * @throws Throwable
     */
    public static function dispatchSync(array $data): void
    {
        self::getInstance($data)->handle();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function getInstance($data)
    {
        $instance = Yii::getContainer()->get(ConsumerInterface::class)(static::class, $data);
        $instance->context = $data;

        return $instance;
    }
}
