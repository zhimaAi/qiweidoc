<?php

declare(strict_types=1);

namespace Yiisoft\Security;

final class AuthenticationException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Failed to decrypt data.');
    }
}
