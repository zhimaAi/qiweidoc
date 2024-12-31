<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\Exceptions;

use RuntimeException;
use Yiisoft\Validator\Result;

class ValidationException extends RuntimeException
{
    public function __construct(private readonly Result $result, $message = '参数验证错误')
    {
        $errors = $result->getFirstErrorMessagesIndexedByAttribute();
        $firstError = reset($errors);
        $firstField = key($errors);
        $message = sprintf("{$message}：%s (%s)", $firstError, $firstField);

        parent::__construct($message, 422);
    }

    public function getResult(): Result
    {
        return $this->result;
    }
}
