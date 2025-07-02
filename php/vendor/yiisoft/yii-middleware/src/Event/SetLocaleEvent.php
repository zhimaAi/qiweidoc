<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware\Event;

use Yiisoft\Yii\Middleware\Locale;

/**
 * Raised when {@see Locale} middleware have determined the locale to use.
 * Use this event to configure locale of extra services.
 */
final class SetLocaleEvent
{
    /**
     * @param string $locale Locale determined by {@see Locale} middleware.
     */
    public function __construct(private string $locale)
    {
    }

    /**
     * Get locale determined by {@see Locale} middleware.
     *
     * @return string Locale to use.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
}
