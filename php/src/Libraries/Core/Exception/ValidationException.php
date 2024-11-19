<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Exception;

use RuntimeException;
use Yiisoft\Validator\Result;

class ValidationException extends RuntimeException
{
    public function __construct(private Result $result, $message = '参数验证错误')
    {
        parent::__construct($message, 422);
    }

    public function getResult(): Result
    {
        return $this->result;
    }
}
