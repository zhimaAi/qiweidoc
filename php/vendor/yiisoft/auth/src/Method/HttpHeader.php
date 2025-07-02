<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Method;

use JetBrains\PhpStorm\Language;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

use function reset;

/**
 * HttpHeader supports HTTP authentication through HTTP Headers.
 *
 * The default implementation of HttpHeader uses the
 * {@see \Yiisoft\Auth\IdentityWithTokenRepositoryInterface::findIdentityByToken()}
 * and passes the value of the `X-Api-Key` header. This implementation is used mainly for authenticating API clients.
 */
class HttpHeader implements AuthenticationMethodInterface
{
    protected string $headerName = 'X-Api-Key';
    private ?string $tokenType = null;

    /**
     * @var string A pattern to use to extract the HTTP authentication value.
     * @psalm-var non-empty-string
     */
    protected string $pattern = '/(.*)/';

    public function __construct(protected IdentityWithTokenRepositoryInterface $identityRepository)
    {
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        $authToken = $this->getAuthenticationToken($request);
        if ($authToken !== null) {
            return $this->identityRepository->findIdentityByToken($authToken, $this->tokenType);
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }

    /**
     * @param string $name The HTTP header name.
     *
     * @return $this
     *
     * @psalm-immutable
     */
    public function withHeaderName(string $name): self
    {
        $new = clone $this;
        $new->headerName = $name;
        return $new;
    }

    /**
     * @param string|null $type Identity token type
     *
     * @return $this
     *
     * @psalm-immutable
     */
    public function withTokenType(?string $type): self
    {
        $new = clone $this;
        $new->tokenType = $type;
        return $new;
    }

    /**
     * @param string $pattern A pattern to use to extract the HTTP authentication value.
     *
     * @return self
     *
     * @psalm-param non-empty-string $pattern
     * @psalm-immutable
     */
    public function withPattern(#[Language('RegExp')] string $pattern): self
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    protected function getAuthenticationToken(ServerRequestInterface $request): ?string
    {
        $authHeaders = $request->getHeader($this->headerName);
        $authHeader = reset($authHeaders);
        if (!empty($authHeader)) {
            if (preg_match($this->pattern, $authHeader, $matches)) {
                $authHeader = $matches[1];
            } else {
                return null;
            }
            return $authHeader;
        }
        return null;
    }
}
