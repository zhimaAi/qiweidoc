<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

use Yiisoft\Hydrator\Exception\NonInstantiableException;

/**
 * Creates or hydrates objects from a set of raw data.
 */
interface HydratorInterface
{
    /**
     * Hydrates an object with data.
     *
     * @param object $object Object to hydrate.
     * @param array|DataInterface $data Data to hydrate an object with.
     *
     * @throws NonInstantiableException
     */
    public function hydrate(object $object, array|DataInterface $data = []): void;

    /**
     * Creates an object and hydrates it with data.
     *
     * @param string $class The class name to create.
     * @param array|DataInterface $data Data to hydrate an object with.
     *
     * @throws NonInstantiableException
     * @return object Created and hydrated object.
     *
     * @psalm-template T as object
     * @psalm-param class-string<T> $class
     * @psalm-return T
     */
    public function create(string $class, array|DataInterface $data = []): object;
}
