<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Job;

/**
 * Jobs Consumer - 简化版消费者定义
 * 支持多队列，可以将任务分配到不同的队列中执行
 */
class Consumer
{
    private string $queueName;
    private string $handler;

    public static function name(string $name): Consumer
    {
        $route = new self();
        $route->queueName = $name;
        return $route;
    }

    public function count(int $count): self
    {
        return $this;
    }

    public function action(string $className): self
    {
        $this->handler = $className;

        return $this;
    }

    public function reserveOnStop(): self
    {
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

    /**
     * 注册方法 - 新版本中不需要注册队列，保留空实现以兼容旧代码
     */
    public function register(): void
    {
        // 新的 jobs 插件不需要预先注册队列，直接 push 即可
    }
}
