<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Psr\Container\NotFoundExceptionInterface;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Exception that is thrown by {@see DiResolver} when an object is not found or object ID auto-resolving fails.
 */
final class DiNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    /**
     * @param ReflectionParameter|ReflectionProperty $reflection Parameter or property reflection.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(ReflectionParameter|ReflectionProperty $reflection, ?Throwable $previous = null)
    {
        /**
         * @psalm-suppress PossiblyNullReference $reflection->getDeclaringClass() always returns not null in this case.
         */
        $className = $reflection->getDeclaringClass()->getName();

        if ($reflection instanceof ReflectionParameter) {
            $message = 'Constructor parameter "' . $reflection->getName() . '" of class "' . $className . '"';
        } else {
            $message = 'Class property "' . $className . '::$' . $reflection->getName() . '"';
        }

        $type = $reflection->getType();
        $message .= $type === null
            ? ' without type'
            : (' with type "' . $type . '"');

        $message .= ' not resolved.';

        parent::__construct($message, previous: $previous);
    }
}
