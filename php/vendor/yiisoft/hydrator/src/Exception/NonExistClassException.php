<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Exception;

/**
 * Thrown when a class is attempted to be instantiated but does not exist.
 */
final class NonExistClassException extends NonInstantiableException
{
    public function __construct(string $class)
    {
        parent::__construct(
            sprintf(
                'Class "%s" not exist.',
                $class
            ),
        );
    }
}
