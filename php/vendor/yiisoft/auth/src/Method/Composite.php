<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Method;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;

/**
 * Composite allows multiple authentication methods at the same time.
 */
final class Composite implements AuthenticationMethodInterface
{
    /**
     * @param AuthenticationMethodInterface[] $methods
     */
    public function __construct(private array $methods)
    {
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        foreach ($this->methods as $method) {
            if (!$method instanceof AuthenticationMethodInterface) {
                throw new \RuntimeException('Authentication method must be an instance of ' . AuthenticationMethodInterface::class . '.');
            }

            $identity = $method->authenticate($request);
            if ($identity !== null) {
                return $identity;
            }
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->methods as $method) {
            $response = $method->challenge($response);
        }
        return $response;
    }
}
