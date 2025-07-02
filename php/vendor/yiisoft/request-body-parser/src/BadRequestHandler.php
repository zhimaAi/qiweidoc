<?php

declare(strict_types=1);

namespace Yiisoft\Request\Body;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Yiisoft\Http\Status;

/**
 * Default handler that is used when there is an error during parsing a request.
 */
final class BadRequestHandler implements BadRequestHandlerInterface
{
    private ResponseFactoryInterface $responseFactory;
    private ?ParserException $parserException = null;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(Status::BAD_REQUEST);
        $response
            ->getBody()
            ->write(Status::TEXTS[Status::BAD_REQUEST]);

        if ($this->parserException !== null) {
            $response
                ->getBody()
                ->write("\n" . $this->parserException->getMessage());
        }

        return $response;
    }

    public function withParserException(ParserException $e): BadRequestHandlerInterface
    {
        $new = clone $this;
        $new->parserException = $e;
        return $new;
    }
}
