<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

/**
 * Message writer writes a set of messages for a specified category and locale.
 */
interface MessageWriterInterface
{
    /**
     * Writes a set of messages for a specified category and locale.
     *
     * @param string $category Category to write messages for.
     * @param string $locale Locale to write messages for.
     * @param array $messages A set of messages to write. The format is the following:
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
     *
     * @psalm-param array<string, array<string, string>> $messages
     */
    public function write(string $category, string $locale, array $messages): void;
}
