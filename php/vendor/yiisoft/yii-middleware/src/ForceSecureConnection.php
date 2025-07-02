<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

use function strcasecmp;

/**
 * Redirects insecure requests from HTTP to HTTPS, and adds headers necessary to enhance the security policy.
 *
 * Middleware adds HTTP Strict-Transport-Security (HSTS) header to each response.
 * The header tells the browser that your site works with HTTPS only.
 *
 * The Content-Security-Policy (CSP) header can force the browser to load page resources only through a secure
 * connection, even if links in the page layout are specified with an unprotected protocol.
 *
 * Note: Prefer forcing HTTPS via web server in case you aren't creating installable product such as CMS and aren't
 * hosting the project on a server where you don't have access to web server configuration.
 */
final class ForceSecureConnection implements MiddlewareInterface
{
    private const DEFAULT_CSP_DIRECTIVES = 'upgrade-insecure-requests; default-src https:';
    private const DEFAULT_HSTS_MAX_AGE = 31_536_000; // 12 months

    private bool $redirect = true;
    private int $statusCode = Status::MOVED_PERMANENTLY;
    private ?int $port = null;

    private bool $addCSP = true;
    private string $cspDirectives = self::DEFAULT_CSP_DIRECTIVES;

    private bool $addHSTS = true;
    private int $hstsMaxAge = self::DEFAULT_HSTS_MAX_AGE;
    private bool $hstsSubDomains = false;

    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * Returns a new instance and enables redirection from HTTP to HTTPS.
     *
     * @param int $statusCode The response status code of redirection.
     * @param int|null $port The redirection port.
     */
    public function withRedirection(int $statusCode = Status::MOVED_PERMANENTLY, int $port = null): self
    {
        $new = clone $this;
        $new->redirect = true;
        $new->port = $port;
        $new->statusCode = $statusCode;
        return $new;
    }

    /**
     * Returns a new instance and disables redirection from HTTP to HTTPS.
     *
     * @see withRedirection()
     */
    public function withoutRedirection(): self
    {
        $new = clone $this;
        $new->redirect = false;
        return $new;
    }

    /**
     * Returns a new instance with added the `Content-Security-Policy` header to response.
     *
     * @param string $directives The directives {@see DEFAULT_CSP_DIRECTIVES}.
     *
     * @see Header::CONTENT_SECURITY_POLICY
     */
    public function withCSP(string $directives = self::DEFAULT_CSP_DIRECTIVES): self
    {
        $new = clone $this;
        $new->addCSP = true;
        $new->cspDirectives = $directives;
        return $new;
    }

    /**
     * Returns a new instance without the `Content-Security-Policy` header in response.
     *
     * @see withCSP()
     */
    public function withoutCSP(): self
    {
        $new = clone $this;
        $new->addCSP = false;
        return $new;
    }

    /**
     * Returns a new instance with added the `Strict-Transport-Security` header to response.
     *
     * @param int $maxAge The max age {@see DEFAULT_HSTS_MAX_AGE}.
     * @param bool $subDomains Whether to add the `includeSubDomains` option to the header value.
     */
    public function withHSTS(int $maxAge = self::DEFAULT_HSTS_MAX_AGE, bool $subDomains = false): self
    {
        $new = clone $this;
        $new->addHSTS = true;
        $new->hstsMaxAge = $maxAge;
        $new->hstsSubDomains = $subDomains;
        return $new;
    }

    /**
     * Returns a new instance without the `Strict-Transport-Security` header in response.
     *
     * @see withHSTS()
     */
    public function withoutHSTS(): self
    {
        $new = clone $this;
        $new->addHSTS = false;
        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->redirect && strcasecmp($request
                ->getUri()
                ->getScheme(), 'http') === 0) {
            $url = (string) $request
                ->getUri()
                ->withScheme('https')
                ->withPort($this->port);

            return $this->addHSTS(
                $this->responseFactory
                    ->createResponse($this->statusCode)
                    ->withHeader(Header::LOCATION, $url)
            );
        }

        return $this->addHSTS($this->addCSP($handler->handle($request)));
    }

    private function addCSP(ResponseInterface $response): ResponseInterface
    {
        return $this->addCSP
            ? $response->withHeader(Header::CONTENT_SECURITY_POLICY, $this->cspDirectives)
            : $response;
    }

    private function addHSTS(ResponseInterface $response): ResponseInterface
    {
        $subDomains = $this->hstsSubDomains ? '; includeSubDomains' : '';
        return $this->addHSTS
            ? $response->withHeader(Header::STRICT_TRANSPORT_SECURITY, "max-age={$this->hstsMaxAge}{$subDomains}")
            : $response;
    }
}
