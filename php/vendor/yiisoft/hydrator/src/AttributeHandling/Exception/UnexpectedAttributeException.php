<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling\Exception;

use InvalidArgumentException;
use Throwable;

/**
 * Thrown when an attribute isn't of the expected class. Used in data and parameter attribute handlers.
 */
final class UnexpectedAttributeException extends InvalidArgumentException
{
    /**
     * @param string $expectedClassName Expected class name of an attribute.
     * @param object $actualObject An actual given object that's not an instance of `$expectedClassName`.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(
        string $expectedClassName,
        object $actualObject,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Expected "%s", but "%s" given.',
                $expectedClassName,
                $actualObject::class
            ),
            $code,
            $previous,
        );
    }
}
