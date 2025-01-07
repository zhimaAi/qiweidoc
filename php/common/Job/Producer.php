<?php

namespace Common\Job;

use Common\Exceptions\RetryableJobException;
use Common\Module;
use Common\Yii;
use Exception;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;
use Throwable;

class Producer
{
    /**
     * @throws Throwable
     */
    private static function resolveRoute(string $className): Consumer
    {
        $routers = Module::getRouterProvider()->getConsumerRouters();

        $router = null;
        foreach ($routers as $r) {
            /* @var Consumer $r */
            if ($r->getHandler() == $className) {
                $router = $r;
                break;
            }
        }

        if (empty($router)) {
            throw new Exception("消费者路由不存在");
        }

        return $router;
    }

    /**
     * 异步执行
     *
     * @throws Throwable
     */
    public static function dispatch(string $className, array $data, int $delay = 0): QueuedTaskInterface
    {
        $router = self::resolveRoute($className);

        $job = new Jobs(Yii::getRpcClient());
        $queueName = Module::getCurrentModuleName() . "_" . $router->getQueueName();
        $queue = $job->connect($queueName);

        $task = $queue->create($className, serialize($data));
        if ($delay > 0) {
            $task = $task->withHeader('deferred_exec_time', time() + $delay);
        }

        return $queue->dispatch($task);
    }

    /**
     * 同步执行
     * @throws RetryableJobException
     */
    public static function dispatchSync(string $className, array $data): void
    {
        (new $className(...$data))->handle();
    }
}
