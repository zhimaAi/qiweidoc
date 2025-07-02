<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler\Exception;

use Exception;

/**
 * UserException is the base class for exceptions that are meant to be shown to end users.
 * Such exceptions are often caused by mistakes of end users.
 */
class UserException extends Exception
{
}
