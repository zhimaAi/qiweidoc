<?php

declare(strict_types=1);

namespace Yiisoft\FriendlyException;

use Throwable;

/**
 * Provides a friendly name and a possible solution for an exception.
 * Error handlers may consider the interface to render additional information right at the error screen.
 */
interface FriendlyExceptionInterface extends Throwable
{
    /**
     * @return string Human understandable exception name.
     */
    public function getName(): string;

    /**
     * @return string|null Suggestion on how to fix an exception.
     *
     * 1. Make it as short as possible.
     * 2. Do not use HTML tags.
     * 3. Use simple markdown.
     */
    public function getSolution(): ?string;
}
