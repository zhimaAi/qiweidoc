<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use Exception;
use Throwable;

final class UnwritableCategorySourceException extends Exception
{
    public function __construct(string $categoryName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf('The category source "%s" does not support writing.', $categoryName),
            $code,
            $previous
        );
    }
}
