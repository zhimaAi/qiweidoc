<?php

declare(strict_types=1);

namespace Yiisoft\Session;

/**
 * @codeCoverageIgnore
 */
final class NullSession implements SessionInterface
{
    public function get(string $key, $default = null)
    {
        return null;
    }

    public function set(string $key, $value): void
    {
    }

    public function close(): void
    {
    }

    public function open(): void
    {
    }

    public function isActive(): bool
    {
        return false;
    }

    public function regenerateId(): void
    {
    }

    public function discard(): void
    {
    }

    public function all(): array
    {
        return [];
    }

    public function remove(string $key): void
    {
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function pull(string $key, $default = null)
    {
        return null;
    }

    public function destroy(): void
    {
    }

    public function getCookieParameters(): array
    {
        return [];
    }

    public function getId(): ?string
    {
        return null;
    }

    public function setId(string $sessionId): void
    {
    }

    public function getName(): string
    {
        return 'null';
    }

    public function clear(): void
    {
    }
}
