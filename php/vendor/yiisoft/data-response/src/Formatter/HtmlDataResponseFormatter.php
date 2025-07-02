<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Formatter;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Stringable;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFormatterInterface;
use Yiisoft\DataResponse\ResponseContentTrait;

use function is_scalar;
use function sprintf;

/**
 * HtmlDataResponseFormatter formats the response data as HTML.
 */
final class HtmlDataResponseFormatter implements DataResponseFormatterInterface
{
    use ResponseContentTrait;

    /**
     * @var string The Content-Type header for the response.
     */
    private string $contentType = 'text/html';

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
            throw new RuntimeException(
                sprintf(
                    'Data must be either a scalar value, null, or a stringable object. %s given.',
                    get_debug_type($data),
                )
            );
        }

        return $this->addToResponse($dataResponse->getResponse(), empty($data) ? null : (string) $data);
    }
}
