<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Exception;

use LogicException;
use ReflectionMethod;

/**
 * Thrown when a class is not instantiable because of non-public constructor.
 */
final class NonPublicConstructorException extends NonInstantiableException
{
    public function __construct(ReflectionMethod $constructor)
    {
        parent::__construct(
            sprintf(
                '%s is not instantiable because of non-public (%s) constructor.',
                $constructor->getDeclaringClass()->getName(),
                $this->getConstructorType($constructor),
            ),
        );
    }

    private function getConstructorType(ReflectionMethod $constructor): string
    {
        if ($constructor->isPrivate()) {
            return 'private';
        }

        if ($constructor->isProtected()) {
            return 'protected';
        }

        throw new LogicException(
            'Exception "NonPublicConstructorException" can be used only for non-public constructors.'
        );
    }
}
