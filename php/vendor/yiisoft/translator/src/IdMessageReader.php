<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use RuntimeException;

/**
 * ID message reader returns ID as message and doesn't support getting all messages at once.
 */
final class IdMessageReader implements MessageReaderInterface
{
    public function getMessage(string $id, string $category, string $locale, array $parameters = []): string
    {
        return $id;
    }

    public function getMessages(string $category, string $locale): array
    {
        throw new RuntimeException('IdMessageReader doesn\'t support getting all messages at once.');
    }
}
