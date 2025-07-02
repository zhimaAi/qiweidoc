<?php

declare(strict_types=1);

namespace Yiisoft\Session;

use DateInterval;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\Cookies\Cookie;

/**
 * Session middleware handles storing session ID into a response cookie and
 * restoring the session associated with the ID from a request cookie.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestSessionId = $this->getSessionIdFromRequest($request);
        if ($requestSessionId !== null && $this->session->getId() === null) {
            $this->session->setId($requestSessionId);
        }

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->session->discard();
            throw $e;
        }

        return $this->commitSession($request, $response);
    }

    /**
     * Close session and add/modify response session cookie if necessary.
     *
     * @throws \Exception
     */
    private function commitSession(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->session->isActive()) {
            $this->session->close();
        }

        $currentSessionId = $this->session->getId();
        if ($currentSessionId === null) {
            return $response;
        }

        if ($this->getSessionIdFromRequest($request) === $currentSessionId) {
            // SID not changed, no need to send new cookie.
            return $response;
        }

        /** @psalm-var array{
         *      lifetime: int,
         *      path: string,
         *      domain: string,
         *      secure: bool,
         *      httponly: bool,
         *      samesite: string
         * }
         */
        $cookieParameters = $this->session->getCookieParameters();

        $cookieDomain = $cookieParameters['domain'];
        if (empty($cookieDomain)) {
            $cookieDomain = $request
                ->getUri()
                ->getHost();
        }

        $useSecureCookie = $cookieParameters['secure'];
        if ($useSecureCookie && $request
                ->getUri()
                ->getScheme() !== 'https') {
            throw new SessionException(
                '"cookie_secure" is on but connection is not secure. ' .
                'Either set Session "cookie_secure" option to "0" or make connection secure.'
            );
        }

        $sessionCookie = (new Cookie($this->session->getName(), $currentSessionId))
            ->withPath($cookieParameters['path'])
            ->withDomain($cookieDomain)
            ->withHttpOnly($cookieParameters['httponly'])
            ->withSecure($useSecureCookie)
            ->withSameSite($cookieParameters['samesite'] ?? Cookie::SAME_SITE_LAX);

        if ($cookieParameters['lifetime'] > 0) {
            $sessionCookie = $sessionCookie->withMaxAge(new DateInterval('PT' . $cookieParameters['lifetime'] . 'S'));
        }

        return $sessionCookie->addToResponse($response);
    }

    private function getSessionIdFromRequest(ServerRequestInterface $request): ?string
    {
        /** @psalm-var array<string, string> $cookies */
        $cookies = $request->getCookieParams();
        return $cookies[$this->session->getName()] ?? null;
    }
}
