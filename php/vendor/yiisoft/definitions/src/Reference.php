<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function is_string;

/**
 * The `Reference` defines a dependency to a service in the container or factory in another service definition.
 * For example:
 *
 * ```php
 * [
 *    InterfaceA::class => ConcreteA::class,
 *    'alternativeForA' => ConcreteB::class,
 *    MyService::class => [
 *        '__construct()' => [
 *            Reference::to('alternativeForA'),
 *        ],
 *    ],
 * ]
 * ```
 */
final class Reference implements ReferenceInterface
{
    private string $id;

    /**
     * @throws InvalidConfigException
     */
    private function __construct(
        mixed $id,
        private readonly bool $optional,
    ) {
        if (!is_string($id)) {
            throw new InvalidConfigException('Reference ID must be string.');
        }

        $this->id = $id;
    }

    /**
     * @throws InvalidConfigException If ID is not string.
     */
    public static function to(mixed $id): self
    {
        return new self($id, false);
    }

    /**
     * Optional reference returns `null` when there is no corresponding definition in container.
     *
     * @param mixed $id ID of the service or object to point to.
     *
     * @throws InvalidConfigException If ID is not string.
     */
    public static function optional(mixed $id): self
    {
        return new self($id, true);
    }

    public function resolve(ContainerInterface $container): mixed
    {
        return (!$this->optional || $container->has($this->id)) ? $container->get($this->id) : null;
    }
}
