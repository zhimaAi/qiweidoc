<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function sprintf;

/**
 * AnyDependency represents a dependency which is composed of a list of other dependencies.
 *
 * The dependency is reported as changed if any sub-dependency is changed.
 */
final class AnyDependency extends Dependency
{
    /**
     * @var Dependency[]
     */
    private readonly array $dependencies;

    /**
     * @param Dependency[] $dependencies List of dependencies that this dependency is composed of.
     * Each array element must be a dependency object.
     *
     * @psalm-suppress DocblockTypeContradiction, RedundantConditionGivenDocblockType
     */
    public function __construct(array $dependencies = [])
    {
        foreach ($dependencies as $dependency) {
            if (!($dependency instanceof Dependency)) {
                throw new InvalidArgumentException(sprintf(
                    'The dependency must be a "%s" instance, "%s" received',
                    Dependency::class,
                    get_debug_type($dependency),
                ));
            }
        }

        $this->dependencies = $dependencies;
    }

    public function evaluateDependency(CacheInterface $cache): void
    {
        foreach ($this->dependencies as $dependency) {
            $dependency->evaluateDependency($cache);
        }
    }

    /**
     * @codeCoverageIgnore Method is not used.
     */
    protected function generateDependencyData(CacheInterface $cache): mixed
    {
        return null;
    }

    public function isChanged(CacheInterface $cache): bool
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency->isChanged($cache)) {
                return true;
            }
        }

        return false;
    }
}
