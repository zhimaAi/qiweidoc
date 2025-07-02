<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use Stringable;

/**
 * Translator translates a message into the specified language.
 */
interface TranslatorInterface
{
    /**
     * Add category sources.
     *
     * @return $this
     */
    public function addCategorySources(CategorySource ...$categories): static;

    /**
     * Set the default locale.
     *
     * @return $this
     */
    public function setLocale(string $locale): static;

    /**
     * @return string Default locale.
     */
    public function getLocale(): string;

    /**
     * Translates a message into the specified language.
     *
     * @param string|Stringable $id The ID of the message to be translated. It can be either artificial ID or the source
     * message.
     * @param array $parameters An array of parameters for the message.
     * @psalm-param array<array-key, mixed> $parameters
     *
     * @param string|null $category The message category. Null means default category.
     * @param string|null $locale The target locale. Null means default locale.
     *
     * @return string The translated message or source string ID if translation was not found or is not required.
     */
    public function translate(
        string|Stringable $id,
        array $parameters = [],
        ?string $category = null,
        ?string $locale = null
    ): string;

    /**
     * Get a new translator instance with category to be used by default in case category isn't specified explicitly.
     *
     * @return $this
     */
    public function withDefaultCategory(string $category): static;

    /**
     * Get a new translator instance with locale to be used by default in case locale isn't specified explicitly.
     *
     * @return $this
     */
    public function withLocale(string $locale): static;
}
