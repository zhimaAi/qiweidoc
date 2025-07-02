<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Exception;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class InvalidArgumentException extends \InvalidArgumentException implements PsrInvalidArgumentException
{
}
