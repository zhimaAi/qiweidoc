<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Exception;

use Yiisoft\Http\Status;

class UnauthorizedException extends \Exception implements ApplicationException
{
    public function __construct($message = "Unauthorized access", $code = Status::UNAUTHORIZED, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
