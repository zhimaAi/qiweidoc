<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Redis;

use Predis\ClientInterface;
use Yiisoft\Mutex\MutexFactory;
use Yiisoft\Mutex\MutexInterface;

/**
 * Allows creating {@see RedisMutex} mutex objects.
 */
final class RedisMutexFactory extends MutexFactory
{
    private ClientInterface $client;
    private int $ttl;

    /**
     * @param ClientInterface $client Predis client instance to use.
     * @param int $ttl Number of seconds in which the lock will be auto released.
     */
    public function __construct(ClientInterface $client, int $ttl = 30)
    {
        $this->client = $client;
        $this->ttl = $ttl;
    }

    public function create(string $name): MutexInterface
    {
        return new RedisMutex($name, $this->client, $this->ttl);
    }
}
