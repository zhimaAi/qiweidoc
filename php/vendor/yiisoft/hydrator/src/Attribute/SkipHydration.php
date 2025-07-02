<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute;

use Attribute;

/**
 * Attribute that marks a class property or constructor parameter for as ignored on hydration.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final class SkipHydration
{
}
