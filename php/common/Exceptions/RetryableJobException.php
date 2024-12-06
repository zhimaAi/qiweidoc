<?php

namespace Common\Exceptions;

use Throwable;

class RetryableJobException extends \Exception implements ApplicationException
{
    private int $delay;

    public function __construct(string $message = "", int $delay = 3, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->delay = $delay;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
