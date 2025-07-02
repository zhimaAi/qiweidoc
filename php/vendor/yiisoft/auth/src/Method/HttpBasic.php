<?php

declare(strict_types=1);

namespace Yiisoft\Auth\Method;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Http\Header;

use function call_user_func;
use function count;

/**
 * HTTP Basic authentication method.
 *
 * @see https://tools.ietf.org/html/rfc7617
 *
 * In case authentication does not work as expected, make sure your web server passes username and password
 * to `$request->getServerParams()['PHP_AUTH_USER']` and `$request->getServerParams()['PHP_AUTH_PW']`
 * parameters. If you are using Apache with PHP-CGI, you might need to add this line to your `.htaccess` file:
 *
 * ```
 * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
 * ```
 */
final class HttpBasic implements AuthenticationMethodInterface
{
    private string $realm = 'api';
    private ?string $tokenType = null;

    /**
     * @var callable|null
     */
    private $authenticationCallback;

    public function __construct(private IdentityWithTokenRepositoryInterface $identityRepository)
    {
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        [$username, $password] = $this->getAuthenticationCredentials($request);

        if ($this->authenticationCallback !== null && ($username !== null || $password !== null)) {
            return call_user_func($this->authenticationCallback, $username, $password, $this->identityRepository);
        }

        if ($username !== null) {
            return $this->identityRepository->findIdentityByToken($username, $this->tokenType);
        }

        return null;
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader(Header::WWW_AUTHENTICATE, "Basic realm=\"{$this->realm}\"");
    }

    /**
     * @param callable $authenticationCallback A PHP callable that will authenticate the user with the HTTP basic
     * authentication information. The callable should have the following signature:
     *
     * ```php
     * static function (
     *     string $username,
     *     string $password,
     *     \Yiisoft\Auth\IdentityRepositoryInterface $identityRepository
     * ): ?\Yiisoft\Auth\IdentityInterface
     * ```
     *
     * It should return an identity object that matches the username and password.
     * Null should be returned if there is no such identity.
     * The callable will be called only if current user is not authenticated.
     *
     * If not set, the username information will be considered as an access token
     * while the password information will be ignored.
     * The {@see \Yiisoft\Auth\IdentityWithTokenRepositoryInterface::findIdentityByToken()}
     * method will be called to authenticate an identity.
     */
    public function withAuthenticationCallback(callable $authenticationCallback): self
    {
        $new = clone $this;
        $new->authenticationCallback = $authenticationCallback;
        return $new;
    }

    /**
     * @param string $realm The HTTP authentication realm.
     *
     * @return $this
     *
     * @psalm-immutable
     */
    public function withRealm(string $realm): self
    {
        $new = clone $this;
        $new->realm = $realm;
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
     * Obtains authentication credentials from request.
     *
     * @return array ['username', 'password'] array.
     */
    private function getAuthenticationCredentials(ServerRequestInterface $request): array
    {
        $username = $request->getServerParams()['PHP_AUTH_USER'] ?? null;
        $password = $request->getServerParams()['PHP_AUTH_PW'] ?? null;
        if ($username !== null || $password !== null) {
            return [$username, $password];
        }

        /*
         * Apache with php-cgi does not pass HTTP Basic authentication to PHP by default.
         * To make it work, add the following line to to your .htaccess file:
         *
         * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
         */
        $token = $this->getTokenFromHeaders($request);
        if ($token !== null && $this->isBasicToken($token)) {
            $credentials = $this->extractCredentialsFromHeader($token);
            if (count($credentials) < 2) {
                return [$credentials[0], null];
            }

            return $credentials;
        }

        return [null, null];
    }

    private function getTokenFromHeaders(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine(Header::AUTHORIZATION);
        if (!empty($header)) {
            return $header;
        }

        return $request->getServerParams()['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    }

    private function extractCredentialsFromHeader(string $authToken): array
    {
        return array_map(
            static fn ($value) => $value === '' ? null : $value,
            explode(':', base64_decode(substr($authToken, 6)), 2)
        );
    }

    private function isBasicToken(string $token): bool
    {
        return strncasecmp($token, 'basic', 5) === 0;
    }
}
