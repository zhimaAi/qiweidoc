<?php

declare(strict_types=1);

namespace Yiisoft\RequestProvider;

final class RequestCookies
{
    /**
     * @var array The cookies in this collection (indexed by the cookie name).
     *
     * @psalm-var array<string, string>
     */
    private array $cookies;

    public function __construct(RequestProviderInterface $requestProvider)
    {
        /** @psalm-var array<string, string> */
        $this->cookies = $requestProvider->get()->getCookieParams();
    }

    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->cookies);
    }
}
