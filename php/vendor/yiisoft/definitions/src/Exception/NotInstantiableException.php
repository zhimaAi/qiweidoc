<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * `NotInstantiableException` represents an exception caused by incorrect dependency injection container or factory
 * configuration or usage.
 */
class NotInstantiableException extends Exception implements ContainerExceptionInterface {}
