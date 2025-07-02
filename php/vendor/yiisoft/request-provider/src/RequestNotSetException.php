<?php

declare(strict_types=1);

namespace Yiisoft\RequestProvider;

use LogicException;
use Throwable;

/**
 * Thrown when request isn't set before.
 */
final class RequestNotSetException extends LogicException
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('Request is not set.', $code, $previous);
    }
}
