<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Job;

use Common\Exceptions\RetryableJobException;
use Common\Module;
use Common\Yii;
use Exception;
use Spiral\Goridge\RPC\RPC;
use Throwable;

/**
 * Jobs Producer - 使用自定义 jobs 插件的生产者
 */
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
     * 异步执行 - 通过 RPC 调用 jobs 插件的 Push 方法
     *
     * @throws Throwable
     */
    public static function dispatch(string $className, array $data): void
    {
        // 解析路由以获取队列名称和验证 handler 存在
        $router = self::resolveRoute($className);
        $queueName = $router->getQueueName();

        $rpc = Yii::getRpcClient();

        // 准备任务数据
        $jobData = [
            'queue' => $queueName,
            'handler' => $className,
            'data' => json_encode([
                'className' => $className,
                'args' => serialize($data),
            ], JSON_UNESCAPED_UNICODE),
        ];

        // 调用 RPC 方法 jobs.Push
        $result = $rpc->call('jobs.Push',  $jobData);

        Yii::logger()->debug('Job dispatched', [
            'queue' => $queueName,
            'className' => $className,
            'data' => $data,
            'result' => $result,
        ]);
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
