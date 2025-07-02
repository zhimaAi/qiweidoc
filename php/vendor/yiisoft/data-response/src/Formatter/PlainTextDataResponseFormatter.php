<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Formatter;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFormatterInterface;
use Yiisoft\DataResponse\ResponseContentTrait;

/**
 * `PlainTextDataResponseFormatter` formats the response data as plain text.
 */
final class PlainTextDataResponseFormatter implements DataResponseFormatterInterface
{
    use ResponseContentTrait;

    /**
     * @var string The Content-Type header for the response.
     */
    private string $contentType = 'text/plain';

    /**
     * @var string The encoding for the Content-Type header.
     */
    private string $encoding = 'UTF-8';

    /**
     * @inheritDoc
     */
    public function format(DataResponse $dataResponse): ResponseInterface
    {
        $data = $dataResponse->getData();

        if (!is_scalar($data) && $data !== null && !$data instanceof Stringable) {
            throw new LogicException(sprintf(
                'Data must be either a scalar value, null, or a stringable object. %s given.',
                get_debug_type($data),
            ));
        }

        return $this->addToResponse($dataResponse->getResponse(), empty($data) ? null : (string) $data);
    }
}
