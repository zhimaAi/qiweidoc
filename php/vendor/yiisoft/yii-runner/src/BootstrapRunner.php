<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner;

use Psr\Container\ContainerInterface;
use RuntimeException;

use function get_debug_type;
use function is_callable;
use function sprintf;

/**
 * Runs application bootstrap configs.
 */
final class BootstrapRunner implements RunnerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $bootstrapList = [],
    ) {
    }

    /**
     * @throws RuntimeException If the bootstrap callback is not callable.
     */
    public function run(): void
    {
        foreach ($this->bootstrapList as $callback) {
            if (!is_callable($callback)) {
                throw new RuntimeException(
                    sprintf(
                        'Bootstrap callback must be callable, "%s" given.',
                        get_debug_type($callback),
                    )
                );
            }

            $callback($this->container);
        }
    }
}
