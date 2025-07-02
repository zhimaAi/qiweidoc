<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

use InvalidArgumentException;

use function array_map;
use function array_slice;
use function base64_decode;
use function base64_encode;
use function ceil;
use function count;
use function implode;
use function max;
use function mb_strlen;
use function mb_strrpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function rtrim;
use function sprintf;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtr;
use function trim;

/**
 * Provides static methods to work with strings.
 */
final class StringHelper
{
    public const DEFAULT_WHITESPACE_PATTERN = "\pC\pZ";

    /**
     * Returns the number of bytes in the given string.
     * This method ensures the string is treated as a byte array even if `mbstring.func_overload` is turned on
     * by using {@see mb_strlen()}.
     *
     * @param string|null $input The string being measured for length.
     *
     * @return int The number of bytes in the given string.
     */
    public static function byteLength(string|null $input): int
    {
        return mb_strlen((string)$input, '8bit');
    }

    /**
     * Returns the portion of string specified by the start and length parameters.
     * This method ensures the string is treated as a byte array by using `mb_substr()`.
     *
     * @param string $input The input string. Must be one character or longer.
     * @param int $start The starting position.
     * @param int|null $length The desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     *
     * @return string The extracted part of string, or FALSE on failure or an empty string.
     *
     * @see https://www.php.net/manual/en/function.substr.php
     */
    public static function byteSubstring(string $input, int $start, ?int $length = null): string
    {
        return mb_substr($input, $start, $length ?? mb_strlen($input, '8bit'), '8bit');
    }

    /**
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, PHP's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     * @param string $suffix If the name component ends in suffix this will also be cut off.
     *
     * @return string The trailing name component of the given path.
     *
     * @see https://www.php.net/manual/en/function.basename.php
     */
    public static function baseName(string $path, string $suffix = ''): string
    {
        $length = mb_strlen($suffix);
        if ($length > 0 && mb_substr($path, -$length) === $suffix) {
            $path = mb_substr($path, 0, -$length);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        $position = mb_strrpos($path, '/');
        if ($position !== false) {
            return mb_substr($path, $position + 1);
        }

        return $path;
    }

    /**
     * Returns parent directory's path.
     * This method is similar to `dirname()` except that it will treat
     * both \ and / as directory separators, independent of the operating system.
     *
     * @param string $path A path string.
     *
     * @return string The parent directory's path.
     *
     * @see https://www.php.net/manual/en/function.basename.php
     */
    public static function directoryName(string $path): string
    {
        $position = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($position !== false) {
            return mb_substr($path, 0, $position);
        }

        return '';
    }

    /**
     * Get part of string.
     *
     * @param string $string To get substring from.
     * @param int $start Character to start at.
     * @param int|null $length Number of characters to get.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.mb-substr.php
     */
    public static function substring(string $string, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
    {
        return mb_substr($string, $start, $length, $encoding);
    }

    /**
     * Replace text within a portion of a string.
     *
     * @param string $string The input string.
     * @param string $replacement The replacement string.
     * @param int $start Position to begin replacing substring at.
     * If start is non-negative, the replacing will begin at the start'th offset into string.
     * If start is negative, the replacing will begin at the start'th character from the end of string.
     * @param int|null $length Length of the substring to be replaced.
     * If given and is positive, it represents the length of the portion of string which is to be replaced.
     * If it is negative, it represents the number of characters from the end of string at which to stop replacing.
     * If it is not given, then it will default to the length of the string; i.e. end the replacing at the end of string.
     * If length is zero then this function will have the effect of inserting replacement into string at the given start offset.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     */
    public static function replaceSubstring(
        string $string,
        string $replacement,
        int $start,
        int|null $length = null,
        string $encoding = 'UTF-8',
    ): string {
        $stringLength = mb_strlen($string, $encoding);

        if ($start < 0) {
            $start = max(0, $stringLength + $start);
        } elseif ($start > $stringLength) {
            $start = $stringLength;
        }

        if ($length !== null && $length < 0) {
            $length = max(0, $stringLength - $start + $length);
        } elseif ($length === null || $length > $stringLength) {
            $length = $stringLength;
        }

        if (($start + $length) > $stringLength) {
            $length = $stringLength - $start;
        }

        return mb_substr($string, 0, $start, $encoding)
            . $replacement
            . mb_substr($string, $start + $length, $stringLength - $start - $length, $encoding);
    }

    /**
     * Check if given string starts with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $input Input string.
     * @param string|null $with Part to search inside the $string.
     *
     * @return bool Returns true if first input starts with second input, false otherwise.
     */
    public static function startsWith(string $input, string|null $with): bool
    {
        return $with === null || str_starts_with($input, $with);
    }

    /**
     * Check if given string starts with specified substring ignoring case.
     * Binary and multibyte safe.
     *
     * @param string $input Input string.
     * @param string|null $with Part to search inside the $string.
     *
     * @return bool Returns true if first input starts with second input, false otherwise.
     */
    public static function startsWithIgnoringCase(string $input, string|null $with): bool
    {
        $bytes = self::byteLength($with);

        if ($bytes === 0) {
            return true;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return self::lowercase(self::substring($input, 0, $bytes, '8bit')) === self::lowercase($with);
    }

    /**
     * Check if given string ends with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $input Input string to check.
     * @param string|null $with Part to search inside of the $string.
     *
     * @return bool Returns true if first input ends with second input, false otherwise.
     */
    public static function endsWith(string $input, string|null $with): bool
    {
        return $with === null || str_ends_with($input, $with);
    }

    /**
     * Check if given string ends with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $input Input string to check.
     * @param string|null $with Part to search inside of the $string.
     *
     * @return bool Returns true if first input ends with second input, false otherwise.
     */
    public static function endsWithIgnoringCase(string $input, string|null $with): bool
    {
        $bytes = self::byteLength($with);

        if ($bytes === 0) {
            return true;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return self::lowercase(mb_substr($input, -$bytes, mb_strlen($input, '8bit'), '8bit')) === self::lowercase($with);
    }

    /**
     * Truncates a string from the beginning to the number of characters specified.
     *
     * @param string $input String to process.
     * @param int $length Maximum length of the truncated string including trim marker.
     * @param string $trimMarker String to append to the beginning.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     */
    public static function truncateBegin(string $input, int $length, string $trimMarker = '…', string $encoding = 'UTF-8'): string
    {
        $inputLength = mb_strlen($input, $encoding);

        if ($inputLength <= $length) {
            return $input;
        }

        $trimMarkerLength = mb_strlen($trimMarker, $encoding);
        return self::replaceSubstring($input, $trimMarker, 0, -$length + $trimMarkerLength, $encoding);
    }

    /**
     * Truncates a string in the middle. Keeping start and end.
     * `StringHelper::truncateMiddle('Hello world number 2', 8)` produces "Hell…r 2".
     *
     * @param string $input The string to truncate.
     * @param int $length Maximum length of the truncated string including trim marker.
     * @param string $trimMarker String to append in the middle of truncated string.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @return string The truncated string.
     */
    public static function truncateMiddle(string $input, int $length, string $trimMarker = '…', string $encoding = 'UTF-8'): string
    {
        $inputLength = mb_strlen($input, $encoding);

        if ($inputLength <= $length) {
            return $input;
        }

        $trimMarkerLength = mb_strlen($trimMarker, $encoding);
        $start = (int)ceil(($length - $trimMarkerLength) / 2);
        $end = $length - $start - $trimMarkerLength;

        return self::replaceSubstring($input, $trimMarker, $start, -$end, $encoding);
    }

    /**
     * Truncates a string from the end to the number of characters specified.
     *
     * @param string $input The string to truncate.
     * @param int $length Maximum length of the truncated string including trim marker.
     * @param string $trimMarker String to append to the end of truncated string.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @return string The truncated string.
     */
    public static function truncateEnd(string $input, int $length, string $trimMarker = '…', string $encoding = 'UTF-8'): string
    {
        $inputLength = mb_strlen($input, $encoding);

        if ($inputLength <= $length) {
            return $input;
        }

        $trimMarkerLength = mb_strlen($trimMarker, $encoding);
        return rtrim(mb_substr($input, 0, $length - $trimMarkerLength, $encoding)) . $trimMarker;
    }

    /**
     * Truncates a string to the number of words specified.
     *
     * @param string $input The string to truncate.
     * @param int $count How many words from original string to include into truncated string.
     * @param string $trimMarker String to append to the end of truncated string.
     *
     * @return string The truncated string.
     */
    public static function truncateWords(string $input, int $count, string $trimMarker = '…'): string
    {
        /** @psalm-var list<string> $words */
        $words = preg_split('/(\s+)/u', trim($input), -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($words) / 2 > $count) {
            $words = array_slice($words, 0, ($count * 2) - 1);
            return implode('', $words) . $trimMarker;
        }

        return $input;
    }

    /**
     * Get string length.
     *
     * @param string $string String to calculate length for.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.mb-strlen.php
     */
    public static function length(string $string, string $encoding = 'UTF-8'): int
    {
        return mb_strlen($string, $encoding);
    }

    /**
     * Counts words in a string.
     */
    public static function countWords(string $input): int
    {
        /** @var array $words */
        $words = preg_split('/\s+/u', $input, -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Make a string lowercase.
     *
     * @param string $string String to process.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.mb-strtolower.php
     */
    public static function lowercase(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower($string, $encoding);
    }

    /**
     * Make a string uppercase.
     *
     * @param string $string String to process.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.mb-strtoupper.php
     */
    public static function uppercase(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($string, $encoding);
    }

    /**
     * Make a string's first character uppercase.
     *
     * @param string $string The string to be processed.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.ucfirst.php
     */
    public static function uppercaseFirstCharacter(string $string, string $encoding = 'UTF-8'): string
    {
        $firstCharacter = self::substring($string, 0, 1, $encoding);
        $rest = self::substring($string, 1, null, $encoding);

        return self::uppercase($firstCharacter, $encoding) . $rest;
    }

    /**
     * Uppercase the first character of each word in a string.
     *
     * @param string $string The valid UTF-8 string to be processed.
     * @param string $encoding The encoding to use, defaults to "UTF-8".
     *
     * @see https://php.net/manual/en/function.ucwords.php
     */
    public static function uppercaseFirstCharacterInEachWord(string $string, string $encoding = 'UTF-8'): string
    {
        /**
         * @var array $words We assume that `$string` is valid UTF-8 string, so `preg_split()` never returns `false`.
         */
        $words = preg_split('/\s/u', $string, -1, PREG_SPLIT_NO_EMPTY);

        $wordsWithUppercaseFirstCharacter = array_map(
            static fn (string $word) => self::uppercaseFirstCharacter($word, $encoding),
            $words
        );

        return implode(' ', $wordsWithUppercaseFirstCharacter);
    }

    /**
     * Encodes string into "Base 64 Encoding with URL and Filename Safe Alphabet" (RFC 4648).
     *
     * > Note: Base 64 padding `=` may be at the end of the returned string.
     * > `=` is not transparent to URL encoding.
     *
     * @see https://tools.ietf.org/html/rfc4648#page-7
     *
     * @param string $input The string to encode.
     *
     * @return string Encoded string.
     *
     * @psalm-template T as string
     * @psalm-param T $input
     * @psalm-return (T is non-empty-string ? non-empty-string : "")
     */
    public static function base64UrlEncode(string $input): string
    {
        return strtr(base64_encode($input), '+/', '-_');
    }

    /**
     * Decodes "Base 64 Encoding with URL and Filename Safe Alphabet" (RFC 4648).
     *
     * @see https://tools.ietf.org/html/rfc4648#page-7
     *
     * @param string $input Encoded string.
     *
     * @return string Decoded string.
     */
    public static function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Split a string to array with non-empty lines.
     * Whitespace from the beginning and end of a each line will be stripped.
     *
     * @param string $string The input string. It must be valid UTF-8 string.
     * @param string $separator The boundary string. It is a part of regular expression
     * so should be taken into account or properly escaped with {@see preg_quote()}. It must be valid UTF-8 string.
     */
    public static function split(string $string, string $separator = '\R'): array
    {
        /**
         * @var string $string We assume that `$string` is valid UTF-8 string, so `preg_replace()` never returns
         * `false`.
         */
        $string = preg_replace('(^\s*|\s*$)', '', $string);

        /**
         * @var array We assume that $separator is prepared by `preg_quote()` and $string is valid UTF-8 string,
         * so `preg_split()` never returns `false`.
         */
        return preg_split('~\s*' . $separator . '\s*~u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param string $path The path of where do you want to write a value to `$array`. The path can be described by
     * a string when each key should be separated by delimiter. If a path item contains delimiter, it can be escaped
     * with "\" (backslash) or a custom delimiter can be used.
     * @param string $delimiter A separator, used to parse string key for embedded object property retrieving. Defaults
     * to "." (dot).
     * @param string $escapeCharacter An escape character, used to escape delimiter. Defaults to "\" (backslash).
     * @param bool $preserveDelimiterEscaping Whether to preserve delimiter escaping in the items of final array (in
     * case of using string as an input). When `false`, "\" (backslashes) are removed. For a "." as delimiter, "."
     * becomes "\.". Defaults to `false`.
     *
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    public static function parsePath(
        string $path,
        string $delimiter = '.',
        string $escapeCharacter = '\\',
        bool $preserveDelimiterEscaping = false
    ): array {
        if (strlen($delimiter) !== 1) {
            throw new InvalidArgumentException('Only 1 character is allowed for delimiter.');
        }

        if (strlen($escapeCharacter) !== 1) {
            throw new InvalidArgumentException('Only 1 escape character is allowed.');
        }

        if ($delimiter === $escapeCharacter) {
            throw new InvalidArgumentException('Delimiter and escape character must be different.');
        }

        if ($path === '') {
            return [''];
        }

        if (!str_contains($path, $delimiter)) {
            if ($preserveDelimiterEscaping) {
                return [$path];
            }

            return [str_replace($escapeCharacter . $escapeCharacter, $escapeCharacter, $path)];
        }

        /** @psalm-var non-empty-list<array{0:string, 1:int}> $matches */
        $matches = preg_split(
            sprintf(
                '/(?<!%1$s)((?>%1$s%1$s)*)%2$s/',
                preg_quote($escapeCharacter, '/'),
                preg_quote($delimiter, '/')
            ),
            $path,
            -1,
            PREG_SPLIT_OFFSET_CAPTURE
        );
        $result = [];
        $countResults = count($matches);
        for ($i = 1; $i < $countResults; $i++) {
            $l = $matches[$i][1] - $matches[$i - 1][1] - strlen($matches[$i - 1][0]) - 1;
            $result[] = $matches[$i - 1][0] . ($l > 0 ? str_repeat($escapeCharacter, $l) : '');
        }
        $result[] = $matches[$countResults - 1][0];

        if ($preserveDelimiterEscaping === true) {
            return $result;
        }

        return array_map(
            static fn (string $key): string => str_replace(
                [
                    $escapeCharacter . $escapeCharacter,
                    $escapeCharacter . $delimiter,
                ],
                [
                    $escapeCharacter,
                    $delimiter,
                ],
                $key
            ),
            $result
        );
    }

    /**
     * Strip Unicode whitespace (with Unicode symbol property White_Space=yes) or other characters from the beginning and end of a string.
     * Input string and pattern are treated as UTF-8.
     *
     * @see https://en.wikipedia.org/wiki/Whitespace_character#Unicode
     * @see https://www.php.net/manual/function.preg-replace
     *
     * @param string|string[] $string The string or an array with strings.
     * @param string $pattern PCRE regex pattern to search for, as UTF-8 string. Use {@see preg_quote()} to quote `$pattern` if it contains
     * special regular expression characters.
     *
     * @psalm-template TKey of array-key
     * @psalm-param string|array<TKey, string> $string
     * @psalm-param non-empty-string $pattern
     * @psalm-return ($string is array ? array<TKey, string> : string)
     *
     * @return string|string[]
     */
    public static function trim(string|array $string, string $pattern = self::DEFAULT_WHITESPACE_PATTERN): string|array
    {
        self::ensureUtf8String($string);
        self::ensureUtf8Pattern($pattern);

        /**
         * @var string|string[] `$string` is correct UTF-8 string and `$pattern` is correct (it should be passed
         * already prepared), so `preg_replace` never returns `null`.
         */
        return preg_replace("#^[$pattern]+|[$pattern]+$#uD", '', $string);
    }

    /**
     * Strip Unicode whitespace (with Unicode symbol property White_Space=yes) or other characters from the beginning of a string.
     *
     * @see self::trim()
     *
     * @param string|string[] $string The string or an array with strings.
     * @param string $pattern PCRE regex pattern to search for, as UTF-8 string. Use {@see preg_quote()} to quote `$pattern` if it contains
     * special regular expression characters.
     *
     * @psalm-template TKey of array-key
     * @psalm-param string|array<TKey, string> $string
     * @psalm-param non-empty-string $pattern
     * @psalm-return ($string is array ? array<TKey, string> : string)
     *
     * @return string|string[]
     */
    public static function ltrim(string|array $string, string $pattern = self::DEFAULT_WHITESPACE_PATTERN): string|array
    {
        self::ensureUtf8String($string);
        self::ensureUtf8Pattern($pattern);

        /**
         * @var string|string[] `$string` is correct UTF-8 string and `$pattern` is correct (it should be passed
         * already prepared), so `preg_replace` never returns `null`.
         */
        return preg_replace("#^[$pattern]+#u", '', $string);
    }

    /**
     * Strip Unicode whitespace (with Unicode symbol property White_Space=yes) or other characters from the end of a string.
     *
     * @see self::trim()
     *
     * @param string|string[] $string The string or an array with strings.
     * @param string $pattern PCRE regex pattern to search for, as UTF-8 string. Use {@see preg_quote()} to quote `$pattern` if it contains
     * special regular expression characters.
     *
     * @psalm-template TKey of array-key
     * @psalm-param string|array<TKey, string> $string
     * @psalm-param non-empty-string $pattern
     * @psalm-return ($string is array ? array<TKey, string> : string)
     *
     * @return string|string[]
     */
    public static function rtrim(string|array $string, string $pattern = self::DEFAULT_WHITESPACE_PATTERN): string|array
    {
        self::ensureUtf8String($string);
        self::ensureUtf8Pattern($pattern);

        /**
         * @var string|string[] `$string` is correct UTF-8 string and `$pattern` is correct (it should be passed
         * already prepared), so `preg_replace` never returns `null`.
         */
        return preg_replace("#[$pattern]+$#uD", '', $string);
    }

    /**
     * Returns the portion of the string that lies between the first occurrence of the `$start` string
     * and the last occurrence of the `$end` string after that.
     *
     * @param string $string The input string.
     * @param string $start The string marking the start of the portion to extract.
     * @param string|null $end The string marking the end of the portion to extract.
     * If the `$end` string is not provided, it defaults to the value of the `$start` string.
     * @return string|null The portion of the string between the first occurrence of
     * `$start` and the last occurrence of `$end`, or null if either `$start` or `$end` cannot be found.
     */
    public static function findBetween(string $string, string $start, ?string $end = null): ?string
    {
        if ($end === null) {
            $end = $start;
        }

        $startPos = mb_strpos($string, $start);

        if ($startPos === false) {
            return null;
        }

        $startPos += mb_strlen($start);
        $endPos = mb_strrpos($string, $end, $startPos);

        if ($endPos === false) {
            return null;
        }

        return mb_substr($string, $startPos, $endPos - $startPos);
    }

    /**
     * Returns the portion of the string between the initial occurrence of the '$start' string
     * and the next occurrence of the '$end' string.
     *
     * @param string $string The input string.
     * @param string $start The string marking the beginning of the segment to extract.
     * @param string|null $end The string marking the termination of the segment.
     * If the '$end' string is not provided, it defaults to the value of the '$start' string.
     * @return string|null Extracted segment, or null if '$start' or '$end' is not present.
     */
    public static function findBetweenFirst(string $string, string $start, ?string $end = null): ?string
    {
        if ($end === null) {
            $end = $start;
        }

        $startPos = mb_strpos($string, $start);

        if ($startPos === false) {
            return null;
        }

        $startPos += mb_strlen($start);
        $endPos = mb_strpos($string, $end, $startPos);

        if ($endPos === false) {
            return null;
        }

        return mb_substr($string, $startPos, $endPos - $startPos);
    }

    /**
     * Returns the portion of the string between the latest '$start' string
     * and the subsequent '$end' string.
     *
     * @param string $string The input string.
     * @param string $start The string marking the beginning of the segment to extract.
     * @param string|null $end The string marking the termination of the segment.
     * If the '$end' string is not provided, it defaults to the value of the '$start' string.
     * @return string|null Extracted segment, or null if '$start' or '$end' is not present.
     */
    public static function findBetweenLast(string $string, string $start, ?string $end = null): ?string
    {
        if ($end === null) {
            $end = $start;
        }

        $endPos = mb_strrpos($string, $end);

        if ($endPos === false) {
            return null;
        }

        $startPos = mb_strrpos(mb_substr($string, 0, $endPos), $start);

        if ($startPos === false) {
            return null;
        }

        $startPos += mb_strlen($start);

        return mb_substr($string, $startPos, $endPos - $startPos);
    }

    /**
     * Checks if a given string matches any of the provided patterns.
     *
     * Note that patterns should be provided without delimiters on both sides. For example, `te(s|x)t`.
     *
     * @see https://www.php.net/manual/reference.pcre.pattern.syntax.php
     * @see https://www.php.net/manual/reference.pcre.pattern.modifiers.php
     *
     * @param string $string The string to match against the patterns.
     * @param string[] $patterns Regular expressions without delimiters on both sides.
     * @param string $flags Flags to apply to all regular expressions.
     */
    public static function matchAnyRegex(string $string, array $patterns, string $flags = ''): bool
    {
        if (empty($patterns)) {
            return false;
        }

        return (new CombinedRegexp($patterns, $flags))->matches($string);
    }

    /**
     * Ensure the pattern is a valid UTF-8 string.
     *
     * @param string $pattern The pattern.
     *
     * @throws InvalidArgumentException
     */
    private static function ensureUtf8Pattern(string $pattern): void
    {
        if (!preg_match('##u', $pattern)) {
            throw new InvalidArgumentException('Pattern is not a valid UTF-8 string.');
        }
    }

    /**
     * Ensure the string is a valid UTF-8 string.
     *
     * @param array|string $string The string.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-param string|string[] $string
     */
    private static function ensureUtf8String(string|array $string): void
    {
        foreach ((array) $string as $s) {
            if (!preg_match('##u', $s)) {
                throw new InvalidArgumentException('String is not a valid UTF-8 string.');
            }
        }
    }
}
