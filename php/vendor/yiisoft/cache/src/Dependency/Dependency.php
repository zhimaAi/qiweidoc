<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Traversable;
use Yiisoft\Cache\CacheInterface;

use function array_key_exists;
use function iterator_to_array;
use function serialize;
use function sha1;

/**
 * Dependency is the base class for cache dependency classes.
 *
 * Child classes should override its {@see Dependency::generateDependencyData()}
 * for generating the actual dependency data.
 */
abstract class Dependency
{
    /**
     * @var mixed The dependency data that is saved in cache and later is compared with the latest dependency data.
     */
    protected mixed $data = null;

    /**
     * @var bool Whether this dependency is reusable or not. True value means that dependent
     * data for this cache dependency will be generated only once per request. This allows you
     * to use the same cache dependency for multiple separate cache calls while generating the same
     * page without an overhead of re-evaluating dependency data each time. Defaults to false.
     */
    protected bool $isReusable = false;

    /**
     * @var array Static storage of cached data for reusable dependencies.
     * @psalm-var array<string, mixed>
     */
    private static array $reusableData = [];

    /**
     * Changes dependency behavior so dependent data for this cache dependency will be generated only once per request.
     * This allows you to use the same cache dependency for multiple separate cache calls while generating the same
     * page without an overhead of re-evaluating dependency data each time.
     */
    public function markAsReusable(): void
    {
        $this->isReusable = true;
    }

    /**
     * Evaluates the dependency by generating and saving the data related with dependency.
     *
     * This method is invoked by cache before writing data into it.
     *
     * @param CacheInterface $cache The cache component that is currently evaluating this dependency.
     */
    public function evaluateDependency(CacheInterface $cache): void
    {
        if (!$this->isReusable) {
            $this->data = $this->generateDependencyData($cache);
            return;
        }

        $hash = $this->generateReusableHash();

        if (!array_key_exists($hash, self::$reusableData)) {
            /** @var mixed */
            self::$reusableData[$hash] = $this->generateDependencyData($cache);
        }

        $this->data = self::$reusableData[$hash];
    }

    /**
     * Checks whether the dependency is changed.
     *
     * @param CacheInterface $cache The cache component that is currently evaluating this dependency
     *
     * @return bool Whether the dependency has changed.
     */
    public function isChanged(CacheInterface $cache): bool
    {
        if (!$this->isReusable) {
            return $this->data !== $this->generateDependencyData($cache);
        }

        $hash = $this->generateReusableHash();

        if (!array_key_exists($hash, self::$reusableData)) {
            /** @var mixed */
            self::$reusableData[$hash] = $this->generateDependencyData($cache);
        }

        return $this->data !== self::$reusableData[$hash];
    }

    /**
     * Resets all cached data for reusable dependencies.
     */
    public static function resetReusableData(): void
    {
        self::$reusableData = [];
    }

    /**
     * Generates a unique hash that can be used for retrieving reusable dependency data.
     *
     * @return string A unique hash value for this cache dependency.
     *
     * @see isReusable()
     */
    protected function generateReusableHash(): string
    {
        /** @var mixed */
        $data = $this->data;
        $this->data = null; // https://github.com/yiisoft/yii2/issues/3052
        $key = sha1(serialize($this));
        $this->data = $data;
        return $key;
    }

    /**
     * Converts iterable to array.
     *
     * @return array
     */
    protected function iterableToArray(iterable $iterable): array
    {
        /** @psalm-suppress RedundantCast */
        return $iterable instanceof Traversable ? iterator_to_array($iterable) : (array) $iterable;
    }

    /**
     * Generates the data needed to determine if dependency is changed.
     *
     * Derived classes should override this method to generate the actual dependency data.
     *
     * @param CacheInterface $cache The cache component that is currently evaluating this dependency.
     *
     * @return mixed The data needed to determine if dependency has been changed.
     */
    abstract protected function generateDependencyData(CacheInterface $cache): mixed;
}
