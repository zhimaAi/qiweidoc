<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http;

use RuntimeException;
use Yiisoft\Validator\Result;

/**
 * `InputValidationException` is thrown when input validation fails and contains a validation result.
 */
final class InputValidationException extends RuntimeException
{
    /**
     * @param Result $result Earlier validation result.
     */
    public function __construct(
        private Result $result,
    ) {
        parent::__construct('Input validation error.');
    }

    /**
     * @return Result Validation result.
     */
    public function getResult(): Result
    {
        return $this->result;
    }
}
