<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware\Exception;

use Exception;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class InvalidLocalesFormatException extends Exception implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Invalid locales format.';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            The specified locales are not in a valid format. Acceptable format is `key => value` array. For example:
            ```
            ['en' => 'en-US', 'uz' => 'uz-UZ'];
            // or
            ['en' => 'en_US', 'uz' => 'uz_UZ'];
            ```
            SOLUTION;
    }
}
