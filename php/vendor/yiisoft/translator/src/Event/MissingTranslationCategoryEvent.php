<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Event;

/**
 * The event is thrown when translation category is missing.
 */
final class MissingTranslationCategoryEvent
{
    /**
     * @param string $category Category that is missing.
     */
    public function __construct(private string $category)
    {
    }

    /**
     * @return string Category that is missing.
     */
    public function getCategory(): string
    {
        return $this->category;
    }
}
