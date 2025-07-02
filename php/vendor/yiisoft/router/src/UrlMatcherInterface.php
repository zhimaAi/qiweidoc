<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * `UrlMatcherInterface` allows finding a matching route given a PSR-8 server request. It is preferred to type-hint
 * against it in case you need to match URL.
 */
interface UrlMatcherInterface
{
    public function match(ServerRequestInterface $request): MatchingResult;
}
