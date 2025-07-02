<?php

declare(strict_types=1);

namespace Yiisoft\Http;

use InvalidArgumentException;
use Yiisoft\Strings\Inflector;

use function in_array;

/**
 * Helps building Content-Disposition header that complies to RFC-6266 and works in majority of modern browsers.
 *
 * @see https://tools.ietf.org/html/rfc6266
 */
final class ContentDispositionHeader
{
    public const ATTACHMENT = 'attachment';
    public const INLINE = 'inline';

    /**
     * @return string Content-Disposition header name.
     */
    public static function name(): string
    {
        return 'Content-Disposition';
    }

    /**
     * Returns Content-Disposition header value that is safe to use with both old and new browsers.
     *
     * Fallback name:
     *
     * - Causes issues if contains non-ASCII characters with codes less than 32 or more than 126.
     * - Causes issues if contains urlencoded characters (starting with `%`) or `%` character. Some browsers interpret
     *   `filename="X"` as urlencoded name, some don't.
     * - Causes issues if contains path separator characters such as `\` or `/`.
     * - Since value is wrapped with `"`, it should be escaped as `\"`.
     * - Since input could contain non-ASCII characters, fallback is obtained by transliteration.
     *
     * UTF name:
     *
     * - Causes issues if contains path separator characters such as `\` or `/`.
     * - Should be urlencoded since headers are ASCII-only.
     * - Could be omitted if it exactly matches fallback name.
     *
     * @param string $type The disposition type.
     * @param string|null $fileName The file name.
     *
     * @throws InvalidArgumentException if `$type` is incorrect.
     *
     * @return string
     */
    public static function value(string $type, ?string $fileName = null): string
    {
        if (!in_array($type, [self::INLINE, self::ATTACHMENT])) {
            throw new InvalidArgumentException(
                'Disposition type must be either "' . self::ATTACHMENT . '" or "' . self::INLINE . '".'
            );
        }

        $header = $type;

        if ($fileName === null) {
            return $header;
        }

        $fileName = str_replace(['%', '/', '\\'], '_', $fileName);

        $fallbackName = (new Inflector())->toTransliterated($fileName, Inflector::TRANSLITERATE_LOOSE);
        $fallbackName = str_replace("\r\n", '_', $fallbackName);
        $fallbackName = preg_replace('/[^\x20-\x7e]/u', '_', $fallbackName);
        $fallbackName = str_replace('"', '\\"', $fallbackName);

        $utfName = rawurlencode($fileName);

        $header .= "; filename=\"{$fallbackName}\"";
        if ($utfName !== $fallbackName) {
            $header .= "; filename*=utf-8''{$utfName}";
        }

        return $header;
    }

    public static function inline(?string $fileName = null): string
    {
        return self::value(self::INLINE, $fileName);
    }

    public static function attachment(?string $fileName = null): string
    {
        return self::value(self::ATTACHMENT, $fileName);
    }
}
