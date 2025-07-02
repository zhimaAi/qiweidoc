<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

/**
 * The result object that provides whether the value is resolved or not, and the value itself.
 */
final class Result
{
    /**
     * @param bool $isResolved Whether the value is resolved.
     * @param mixed $value The value.
     */
    private function __construct(
        private bool $isResolved,
        private mixed $value = null,
    ) {
    }

    /**
     * Creates instance of `Result` with resolved value.
     *
     * @param mixed $value The resolved value.
     *
     * @return self The result object.
     */
    public static function success(mixed $value): self
    {
        return new self(true, $value);
    }

    /**
     * Creates instance of `Result` without value.
     *
     * @return self The result object.
     */
    public static function fail(): self
    {
        return new self(false);
    }

    /**
     * @return bool Whether the value is resolved.
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * Returns the resolved value.
     *
     * When the value is not resolved, this method returns `null`.
     * Since `null` can be a valid value as well, please use {@see isResolved()} to check
     * if the value is resolved or not.
     *
     * @return mixed The resolved value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
