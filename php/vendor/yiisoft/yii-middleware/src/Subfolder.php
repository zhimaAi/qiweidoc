<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Middleware\Exception\BadUriPrefixException;

use function dirname;
use function strlen;

/**
 * This middleware supports routing when the entry point of the application isn't directly at the webroot.
 * By default, it determines webroot based on server parameters.
 *
 * You should place this middleware before `Route` middleware in the middleware list.
 */
final class Subfolder implements MiddlewareInterface
{
    /**
     * @param UrlGeneratorInterface $uriGenerator The URI generator instance.
     * @param Aliases $aliases The aliases instance.
     * @param string|null $prefix URI prefix that goes immediately after the domain part.
     * The prefix value usually begins with a slash and mustn't end with a slash.
     * @param string|null $baseUrlAlias The base URL alias {@see Aliases::get()}. Defaults to `@baseUrl`.
     */
    public function __construct(
        private UrlGeneratorInterface $uriGenerator,
        private Aliases $aliases,
        private ?string $prefix = null,
        private ?string $baseUrlAlias = '@baseUrl',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $baseUrl = $this->prefix ?? $this->getBaseUrl($request);
        $length = strlen($baseUrl);

        if ($this->prefix !== null) {
            if (empty($this->prefix)) {
                throw new BadUriPrefixException('URI prefix can\'t be empty.');
            }

            if ($baseUrl[-1] === '/') {
                throw new BadUriPrefixException('Wrong URI prefix value.');
            }

            if (!str_starts_with($path, $baseUrl)) {
                throw new BadUriPrefixException('URI prefix doesn\'t match.');
            }
        }

        if ($length > 0) {
            $newPath = substr($path, $length);

            if ($newPath === '') {
                $newPath = '/';
            }

            if ($newPath[0] === '/') {
                $request = $request->withUri($uri->withPath($newPath));
                $this->uriGenerator->setUriPrefix($baseUrl);
                if ($this->baseUrlAlias !== null && $this->prefix === null) {
                    $this->aliases->set($this->baseUrlAlias, $baseUrl);
                }
            } elseif ($this->prefix !== null) {
                throw new BadUriPrefixException('URI prefix doesn\'t match completely.');
            }
        }

        return $handler->handle($request);
    }

    private function getBaseUrl(ServerRequestInterface $request): string
    {
        /**
         * @var array{
         *     SCRIPT_FILENAME?:string,
         *     PHP_SELF?:string,
         *     ORIG_SCRIPT_NAME?:string,
         *     DOCUMENT_ROOT?:string
         * } $serverParams
         */
        $serverParams = $request->getServerParams();
        $scriptUrl = $serverParams['SCRIPT_FILENAME'] ?? '/index.php';
        $scriptName = basename($scriptUrl);

        if (isset($serverParams['PHP_SELF']) && basename($serverParams['PHP_SELF']) === $scriptName) {
            $scriptUrl = $serverParams['PHP_SELF'];
        } elseif (
            isset($serverParams['ORIG_SCRIPT_NAME']) &&
            basename($serverParams['ORIG_SCRIPT_NAME']) === $scriptName
        ) {
            $scriptUrl = $serverParams['ORIG_SCRIPT_NAME'];
        } elseif (
            isset($serverParams['PHP_SELF']) &&
            ($pos = strpos($serverParams['PHP_SELF'], $scriptName)) !== false
        ) {
            $scriptUrl = substr($serverParams['PHP_SELF'], 0, $pos + strlen($scriptName));
        } elseif (
            !empty($serverParams['DOCUMENT_ROOT']) &&
            str_starts_with($scriptUrl, $serverParams['DOCUMENT_ROOT'])
        ) {
            $scriptUrl = str_replace([$serverParams['DOCUMENT_ROOT'], '\\'], ['', '/'], $scriptUrl);
        }

        return rtrim(dirname($scriptUrl), '\\/');
    }
}
