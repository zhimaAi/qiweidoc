<?php

declare(strict_types=1);

namespace Yiisoft\I18n;

/**
 * LocaleProvider is a stateful service that stores current locale.
 * Other services may use it as a dependency.
 */
final class LocaleProvider
{
    private ?Locale $locale = null;

    public function __construct(
        private Locale $defaultLocale
    ) {
    }

    public function get(): Locale
    {
        return $this->locale ?? $this->defaultLocale;
    }

    public function set(Locale $locale): void
    {
        $this->locale = $locale;
    }

    public function isDefaultLocale(): bool
    {
        return $this->locale === null || $this->locale->asString() === $this->defaultLocale->asString();
    }
}
