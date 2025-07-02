<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware\Exception;

use Exception;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class BadUriPrefixException extends Exception implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Bad URI prefix';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            Most likely you have specified the wrong URI prefix.
            Make sure that path from the web address contains the specified prefix (immediately after the domain part).
            The prefix value usually begins with a slash and must not end with a slash.
            The prefix should be exact match. We're not trimming it or adding anything to it.
            SOLUTION;
    }
}
