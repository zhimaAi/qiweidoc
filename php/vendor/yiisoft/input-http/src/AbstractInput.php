<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http;

use Yiisoft\Hydrator\Validator\ValidatedInputInterface;
use Yiisoft\Hydrator\Validator\ValidatedInputTrait;

/**
 * A base class for all validated request input DTOs.
 */
abstract class AbstractInput implements RequestInputInterface, ValidatedInputInterface
{
    use ValidatedInputTrait;
}
