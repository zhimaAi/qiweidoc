<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core\Exception;

use Throwable;

class WechatRequestException extends \Exception implements ApplicationException
{
    const STATIC_ERROR = 1; //请求企微接口过程中异常
    const STATIC_FAIL = 2;  //企微返回结果了但是有错误

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
