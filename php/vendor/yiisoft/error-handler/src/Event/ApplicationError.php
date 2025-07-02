<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler\Event;

use Throwable;

/**
 * `ApplicationError` represents an application error event.
 */
final class ApplicationError
{
    public function __construct(private Throwable $throwable)
    {
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
