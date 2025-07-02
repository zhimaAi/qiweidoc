<?php

declare(strict_types=1);

namespace Yiisoft\Security;

use Exception;
use InvalidArgumentException;
use Yiisoft\Strings\StringHelper;

/**
 * Random allows generating random values.
 *
 * Currently, it has a single method "string".
 * The following extras are available via PHP directly:
 *
 * - `random_bytes()` for bytes. Note that output may not be ASCII.
 * - `random_int()` for integers.
 */
final class Random
{
    /**
     * Generates a random string of specified length.
     * The string generated matches [A-Za-z0-9_-]+ and is transparent to URL-encoding.
     *
     * @param int $length The length of the key in characters.
     *
     * @throws Exception On failure.
     *
     * @return string The generated random key.
     *
     * @psalm-return non-empty-string
     */
    public static function string(int $length = 32): string
    {
        if ($length < 1) {
            throw new InvalidArgumentException('First parameter ($length) must be greater than 0.');
        }

        /**
         * Optimization: we can generate a quarter fewer bits to completely cover the desired length in base64
         * @psalm-suppress ArgumentTypeCoercion
         */
        $bytes = random_bytes((int) ceil($length * 0.75));

        /** @var non-empty-string */
        return substr(StringHelper::base64UrlEncode($bytes), 0, $length);
    }
}
