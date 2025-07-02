<?php

declare(strict_types=1);

namespace Yiisoft\Mutex\Redis;

use InvalidArgumentException;
use Predis\ClientInterface;
use Yiisoft\Mutex\Exception\MutexReleaseException;
use Yiisoft\Mutex\MutexInterface;
use Yiisoft\Mutex\RetryAcquireTrait;
use Yiisoft\Security\Random;

use function md5;
use function time;

/**
 * RedisMutex implements mutex "lock" mechanism via Redis locks.
 */
final class RedisMutex implements MutexInterface
{
    use RetryAcquireTrait;

    private ClientInterface $client;
    private string $lockKey;
    private string $lockValue;
    private string $mutexName;
    private ?int $expired = null;
    private int $ttl;

    /**
     * @param string $name Mutex name.
     * @param ClientInterface $client Predis client instance to use.
     * @param int $ttl Number of seconds in which the lock will be auto released.
     */
    public function __construct(string $name, ClientInterface $client, int $ttl = 30)
    {
        if ($ttl < 1) {
            throw new InvalidArgumentException(
                "TTL must be a positive number greater than zero, \"$ttl\" is received.",
            );
        }

        $this->client = $client;
        $this->lockKey = md5(self::class . $name);
        $this->lockValue = Random::string(20);
        $this->mutexName = $name;
        $this->ttl = $ttl;
    }

    public function __destruct()
    {
        $this->release();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://redis.io/topics/distlock
     */
    public function acquire(int $timeout = 0): bool
    {
        return $this->retryAcquire($timeout, function (): bool {
            if (
                !$this->isExpired()
                || $this->client->set($this->lockKey, $this->lockValue, 'EX', $this->ttl, 'NX') === null
            ) {
                return false;
            }

            $this->expired = $this->ttl + time();
            return true;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @see https://redis.io/topics/distlock
     */
    public function release(): void
    {
        if ($this->isExpired()) {
            return;
        }

        $released = (bool) $this->client->eval(
            <<<LUA
                if redis.call('GET',KEYS[1])==ARGV[1] then
                    return redis.call('DEL',KEYS[1])
                else
                    return 0
                end
            LUA,
            1,
            $this->lockKey,
            $this->lockValue,
        );

        if (!$released) {
            throw new MutexReleaseException("Unable to release the \"$this->mutexName\" mutex.");
        }

        $this->expired = null;
    }

    /**
     * Checks whether a lock has been set and whether it has expired.
     *
     * @return bool Whether a lock has been set and whether it has expired.
     */
    private function isExpired(): bool
    {
        return $this->expired === null || $this->expired <= time();
    }
}
