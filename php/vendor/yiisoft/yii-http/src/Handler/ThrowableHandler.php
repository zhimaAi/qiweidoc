<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Http\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Re-throws throwable.
 */
final class ThrowableHandler implements RequestHandlerInterface
{
    public function __construct(private Throwable $throwable)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw $this->throwable;
    }
}
