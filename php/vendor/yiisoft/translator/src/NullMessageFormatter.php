<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

final class NullMessageFormatter implements MessageFormatterInterface
{
    public function format(string $message, array $parameters, string $locale): string
    {
        return $message;
    }
}
