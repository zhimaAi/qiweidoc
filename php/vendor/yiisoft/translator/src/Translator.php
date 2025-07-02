<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Stringable;
use Yiisoft\I18n\Locale;
use Yiisoft\Translator\Event\MissingTranslationCategoryEvent;
use Yiisoft\Translator\Event\MissingTranslationEvent;

/**
 * Translator translates a message into the specified language.
 */
final class Translator implements TranslatorInterface
{
    private MessageFormatterInterface $defaultMessageFormatter;

    /**
     * @var array Array of category message sources indexed by category names.
     * @psalm-var array<string,CategorySource[]>
     */
    private array $categorySources = [];

    /**
     * @psalm-var array<string,true>
     */
    private array $dispatchedMissingTranslationCategoryEvents = [];

    /**
     * @param string $locale Default locale to use if locale is not specified explicitly.
     * @param string|null $fallbackLocale Locale to use if message for the locale specified was not found. Null for
     * none.
     * @param EventDispatcherInterface|null $eventDispatcher Event dispatcher for translation events. Null for none.
     */
    public function __construct(
        private string $locale = 'en-US',
        private ?string $fallbackLocale = null,
        private string $defaultCategory = 'app',
        private ?EventDispatcherInterface $eventDispatcher = null,
        ?MessageFormatterInterface $defaultMessageFormatter = null,
    ) {
        $this->defaultMessageFormatter = $defaultMessageFormatter ?? new NullMessageFormatter();
    }

    public function addCategorySources(CategorySource ...$categories): static
    {
        foreach ($categories as $category) {
            if (isset($this->categorySources[$category->getName()])) {
                $this->categorySources[$category->getName()][] = $category;
            } else {
                $this->categorySources[$category->getName()] = [$category];
            }
        }

        return $this;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function translate(
        string|Stringable $id,
        array $parameters = [],
        ?string $category = null,
        ?string $locale = null
    ): string {
        $locale ??= $this->locale;

        $category ??= $this->defaultCategory;

        if (empty($this->categorySources[$category])) {
            $this->dispatchMissingTranslationCategoryEvent($category);
            return $this->defaultMessageFormatter->format((string) $id, $parameters, $this->fallbackLocale ?? $locale);
        }

        return $this->translateUsingCategorySources((string) $id, $parameters, $category, $locale);
    }

    public function withDefaultCategory(string $category): static
    {
        if (!isset($this->categorySources[$category])) {
            throw new RuntimeException('Category with name "' . $category . '" does not exist.');
        }

        $new = clone $this;
        $new->defaultCategory = $category;
        return $new;
    }

    public function withLocale(string $locale): static
    {
        $new = clone $this;
        $new->setLocale($locale);
        return $new;
    }

    private function translateUsingCategorySources(
        string $id,
        array $parameters,
        string $category,
        string $locale
    ): string {
        $sourceCategory = end($this->categorySources[$category]);
        do {
            $message = $sourceCategory->getMessage($id, $locale, $parameters);

            if ($message !== null) {
                return $sourceCategory->format($message, $parameters, $locale, $this->defaultMessageFormatter);
            }

            $this->eventDispatcher?->dispatch(new MissingTranslationEvent($sourceCategory->getName(), $locale, $id));
        } while (($sourceCategory = prev($this->categorySources[$category])) !== false);

        $localeObject = new Locale($locale);
        $fallback = $localeObject->fallbackLocale();

        if ($fallback->asString() !== $localeObject->asString()) {
            return $this->translateUsingCategorySources($id, $parameters, $category, $fallback->asString());
        }

        if (!empty($this->fallbackLocale)) {
            $fallbackLocaleObject = (new Locale($this->fallbackLocale))->fallbackLocale();
            if ($fallbackLocaleObject->asString() !== $localeObject->asString()) {
                return $this->translateUsingCategorySources($id, $parameters, $category, $fallbackLocaleObject->asString());
            }
        }

        return end($this->categorySources[$category])->format(
            $id,
            $parameters,
            $locale,
            $this->defaultMessageFormatter
        );
    }

    private function dispatchMissingTranslationCategoryEvent(string $category): void
    {
        if (
            $this->eventDispatcher !== null
            && !isset($this->dispatchedMissingTranslationCategoryEvents[$category])
        ) {
            $this->dispatchedMissingTranslationCategoryEvents[$category] = true;
            $this->eventDispatcher->dispatch(new MissingTranslationCategoryEvent($category));
        }
    }
}
