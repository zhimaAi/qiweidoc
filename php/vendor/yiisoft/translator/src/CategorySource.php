<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use RuntimeException;

/**
 * Represents message category.
 */
final class CategorySource
{
    private string $name;

    /**
     * @param string $name Category name.
     * @param MessageReaderInterface $reader Message reader to get messages from for this category.
     * @param MessageFormatterInterface|null $formatter Message formatter to format messages with for this category.
     * @param MessageWriterInterface|null $writer Message writer to write messages for this category.
     */
    public function __construct(
        string $name,
        private MessageReaderInterface $reader,
        private ?MessageFormatterInterface $formatter = null,
        private ?MessageWriterInterface $writer = null,
    ) {
        if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
            throw new RuntimeException('Category name is invalid. Only letters and numbers are allowed.');
        }

        $this->name = $name;
    }

    /**
     * @return string Category name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a message with ID, locale and parameters specified.
     *
     * @param string $id Message ID.
     * @param string $locale Locale to get message for.
     * @param array $parameters Message parameters.
     *
     * @return string|null Message string or null if message was not found.
     */
    public function getMessage(string $id, string $locale, array $parameters = []): ?string
    {
        return $this->reader->getMessage($id, $this->name, $locale, $parameters);
    }

    /**
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
     *
     * @see MessageReaderInterface::getMessages()
     */
    public function getMessages(string $locale): array
    {
        return $this->reader->getMessages($this->name, $locale);
    }

    /**
     * Writes a set of messages for a specified category and locale.
     *
     * @psalm-param array<string, array<string, string>> $messages
     *
     * @param string $locale Locale to write messages for.
     * @param array $messages A set of messages to write. The format is the following:
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
     * @throws UnwritableCategorySourceException When $write is not configured, or it's impossible to write the messages into the source.
     */
    public function write(string $locale, array $messages): void
    {
        if ($this->writer === null) {
            throw new UnwritableCategorySourceException($this->name);
        }
        $this->writer->write($this->name, $locale, $messages);
    }

    /**
     * Format the message given parameters and locale.
     *
     * @param string $message Message to be formatted.
     * @param array $parameters Parameters to use.
     * @param string $locale Locale to use. Usually affects formatting numbers, dates etc.
     * @param MessageFormatterInterface $defaultFormatter Message formatter that will be used if formatter not specified
     * in message category.
     *
     * @return string Formatted message.
     */
    public function format(
        string $message,
        array $parameters,
        string $locale,
        MessageFormatterInterface $defaultFormatter
    ): string {
        return ($this->formatter ?? $defaultFormatter)->format($message, $parameters, $locale);
    }
}
