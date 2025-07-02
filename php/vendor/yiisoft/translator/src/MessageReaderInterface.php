<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

/**
 * Reader obtains a translated message for a given locale by ID and category. Parameters may be used to decide
 * about the message to obtain as well.
 */
interface MessageReaderInterface
{
    /**
     * Obtains a translation message.
     *
     * @param string $id ID of the message to get.
     * @param string $category Category of the message to get.
     * @param string $locale Locale of the message to get.
     * @param array $parameters Parameters that may be used to decide about the message to obtain.
     *
     * @return string|null The translated message or null if translation wasn't found.
     */
    public function getMessage(string $id, string $category, string $locale, array $parameters = []): ?string;

    /**
     * @param string $category Category of messages to get.
     * @param string $locale Locale of messages to get.
     *
     * @psalm-return array<string, array<string, string>>
     *
     * @return array All messages from category. The format is the following:
     *
     * ```php
     * [
     *   'key1' => [
     *     'message' => 'translation1',
     *     // Extra metadata that writer may use:
     *     'comment' => 'Translate carefully!',
     *   ],
     *   'key2' => [
     *     'message' => 'translation2',
     *     // Extra metadata that writer may use:
     *     'comment' => 'Translate carefully!',
     *   ],
     * ]
     * ```
     */
    public function getMessages(string $category, string $locale): array;
}
