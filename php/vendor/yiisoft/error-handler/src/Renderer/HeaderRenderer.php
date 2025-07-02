<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler\Renderer;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\ErrorHandler\ErrorData;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;

/**
 * Formats throwable into HTTP headers.
 */
final class HeaderRenderer implements ThrowableRendererInterface
{
    public function render(Throwable $t, ServerRequestInterface $request = null): ErrorData
    {
        return new ErrorData('', ['X-Error-Message' => self::DEFAULT_ERROR_MESSAGE]);
    }

    public function renderVerbose(Throwable $t, ServerRequestInterface $request = null): ErrorData
    {
        return new ErrorData('', [
            'X-Error-Type' => $t::class,
            'X-Error-Message' => $t->getMessage(),
            'X-Error-Code' => (string) $t->getCode(),
            'X-Error-File' => $t->getFile(),
            'X-Error-Line' => (string) $t->getLine(),
        ]);
    }
}
