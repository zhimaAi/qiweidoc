<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * `InvalidConfigException` is thrown when definition configuration is not valid.
 */
final class InvalidConfigException extends Exception implements ContainerExceptionInterface {}
