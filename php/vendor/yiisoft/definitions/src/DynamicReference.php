<?php

declare(strict_types=1);

namespace Yiisoft\Definitions;

use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\Normalizer;

use function is_callable;
use function is_object;

/**
 * The `DynamicReference` defines a dependency to a service not defined in the container.
 * Definition may be defined multiple ways ({@see Normalizer}). For example:
 *
 * ```php
 * [
 *    MyService::class => [
 *        '__construct()' => [
 *            DynamicReference::to([
 *                'class' => SomeClass::class,
 *                '$someProp' => 15
 *            ])
 *        ]
 *    ]
 * ]
 * ```
 */
final class DynamicReference implements ReferenceInterface
{
    private DefinitionInterface $definition;

    /**
     * @throws InvalidConfigException
     */
    private function __construct(mixed $definition)
    {
        if (is_object($definition) && !is_callable($definition)) {
            throw new InvalidConfigException('DynamicReference don\'t support object as definition.');
        }

        $this->definition = Normalizer::normalize($definition);
    }

    /**
     * @see Normalizer
     *
     * @throws InvalidConfigException If definition is not valid.
     */
    public static function to(mixed $id): self
    {
        return new self($id);
    }

    public function resolve(ContainerInterface $container): mixed
    {
        return $this->definition->resolve($container);
    }
}
