<?php

declare(strict_types=1);

namespace Yiisoft\Request\Body\Parser;

use JsonException;
use Yiisoft\Request\Body\ParserException;
use Yiisoft\Request\Body\ParserInterface;

use function is_array;
use function is_object;
use function json_decode;

/**
 * Parses `application/json` requests where JSON is in the body.
 */
final class JsonParser implements ParserInterface
{
    private bool $convertToAssociativeArray;
    private int $depth;
    private int $options;

    /**
     * @param bool $convertToAssociativeArray Whether objects should be converted to associative array during parsing.
     * @param int $depth Maximum JSON recursion depth.
     * @param int $options JSON decoding options. {@see json_decode()}.
     */
    public function __construct(
        bool $convertToAssociativeArray = true,
        int $depth = 512,
        int $options = JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE
    ) {
        $this->convertToAssociativeArray = $convertToAssociativeArray;
        $this->depth = $depth;
        $this->options = $options;
    }

    public function parse(string $rawBody)
    {
        if ($rawBody === '') {
            return null;
        }

        try {
            /** @var mixed $result */
            $result = json_decode($rawBody, $this->convertToAssociativeArray, $this->depth, $this->options);
            if (is_array($result) || is_object($result)) {
                return $result;
            }
        } catch (JsonException $e) {
            throw new ParserException('Invalid JSON data in request body: ' . $e->getMessage());
        }

        return null;
    }
}
