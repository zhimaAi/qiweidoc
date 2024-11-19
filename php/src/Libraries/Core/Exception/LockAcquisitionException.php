<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Exception;

use Throwable;

class LockAcquisitionException extends \Exception implements ApplicationException
{
    public function __construct(string $message = "获取锁失败", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
