<?php

declare(strict_types=1);

namespace Yiisoft\Request\Body;

/**
 * Parser allows to parse raw server request body.
 */
interface ParserInterface
{
    /**
     * Parse raw server request body.
     *
     * @param string $rawBody Raw server request body.
     *
     * @throws ParserException when parsing can not be done.
     *
     * @return array|object|null Parsing result.
     */
    public function parse(string $rawBody);
}
