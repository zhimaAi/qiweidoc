<?php

declare(strict_types=1);

namespace Yiisoft\Request\Body;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forms response when there is an error parsing request.
 */
interface BadRequestHandlerInterface extends RequestHandlerInterface
{
    /**
     * Creates new instance of handler with parser exception set.
     *
     * @param ParserException $e Exception occurred during parsing request.
     *
     * @return self
     */
    public function withParserException(ParserException $e): self;
}
