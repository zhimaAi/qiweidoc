<?php

declare(strict_types=1);

namespace Yiisoft\RequestProvider;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides a way to set the current request and then get it when needed.
 */
interface RequestProviderInterface
{
    /**
     * Set the current request.
     *
     * @param ServerRequestInterface $request The request to set.
     */
    public function set(ServerRequestInterface $request): void;

    /**
     * Get the current request.
     *
     * @throws RequestNotSetException
     */
    public function get(): ServerRequestInterface;
}
