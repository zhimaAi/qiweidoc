<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Debug;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;

final class AuthenticationMethodInterfaceProxy implements AuthenticationMethodInterface
{
    public function __construct(private AuthenticationMethodInterface $decorated, private IdentityCollector $collector)
    {
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        $identity = null;
        try {
            $identity = $this->decorated->authenticate($request);
        } finally {
            $this->collector->collect($identity);
        }
        return $identity;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $this->decorated->challenge($response);
    }
}
