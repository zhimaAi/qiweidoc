<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use MessageFormatter;
use RuntimeException;

use function extension_loaded;

final class IntlMessageFormatter implements MessageFormatterInterface
{
    public function __construct()
    {
        if (!extension_loaded('intl')) {
            throw new RuntimeException(
                'In order to use intl message formatter, intl extension must be installed and enabled.'
            );
        }
    }

    /**
     * This method uses {{@see MessageFormatter::format()}}
     *
     * @link https://php.net/manual/en/messageformatter.format.php
     */
    public function format(string $message, array $parameters, string $locale): string
    {
        if ($parameters === []) {
            return $message;
        }

        $formatter = new MessageFormatter($locale, $message);

        $result = $formatter->format($parameters);

        if ($result === false) {
            return $message;
        }

        return $result;
    }
}
