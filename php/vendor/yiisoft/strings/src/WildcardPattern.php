<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function strtr;

/**
 * A wildcard pattern to match strings against.
 *
 * - `\` escapes other special characters if usage of escape character is not turned off.
 * - `*` matches any string including the empty string except it has a delimiter (`/` and `\` by default).
 * - `**` matches any string including the empty string and delimiters.
 * - `?` matches any single character.
 * - `[seq]` matches any character in seq.
 * - `[a-z]` matches any character from a to z.
 * - `[!seq]` matches any character not in seq.
 * - `[[:alnum:]]` matches POSIX style character classes,
 *   see {@see https://www.php.net/manual/en/regexp.reference.character-classes.php}.
 */
final class WildcardPattern
{
    private bool $ignoreCase = false;

    /**
     * @psalm-var non-empty-string|null
     */
    private ?string $patternPrepared = null;

    /**
     * @param string $pattern The shell wildcard pattern to match against.
     * @param string[] $delimiters Delimiters to consider for "*" (`/` and `\` by default).
     */
    public function __construct(
        private string $pattern,
        private array $delimiters = ['\\\\', '/'],
    ) {
    }

    /**
     * Checks if the passed string would match the given shell wildcard pattern.
     *
     * @param string $string The tested string.
     *
     * @return bool Whether the string matches pattern or not.
     */
    public function match(string $string): bool
    {
        if ($this->pattern === '**') {
            return true;
        }

        return preg_match($this->getPatternPrepared(), $string) === 1;
    }

    /**
     * Make pattern case insensitive.
     */
    public function ignoreCase(bool $flag = true): self
    {
        $new = clone $this;
        $new->patternPrepared = null;
        $new->ignoreCase = $flag;

        return $new;
    }

    /**
     * Returns whether the pattern contains a dynamic part i.e.
     * has unescaped "*",  "{", "?", or "[" character.
     *
     * @param string $pattern The pattern to check.
     *
     * @return bool Whether the pattern contains a dynamic part.
     */
    public static function isDynamic(string $pattern): bool
    {
        /** @var string $pattern `$rule` and `$replacement` always correct, so `preg_replace` always returns string */
        $pattern = preg_replace('/\\\\./', '', $pattern);
        return preg_match('/[*{?\[]/', $pattern) === 1;
    }

    /**
     * Escapes pattern characters in a string.
     *
     * @param string $string Source string.
     *
     * @return string String with pattern characters escaped.
     */
    public static function quote(string $string): string
    {
        /** @var string `$rule` and `$replacement` always correct, so `preg_replace` always returns string */
        return preg_replace('#([\\\\?*\\[\\]])#', '\\\\$1', $string);
    }

    /**
     * @return non-empty-string
     */
    private function getPatternPrepared(): string
    {
        if ($this->patternPrepared !== null) {
            return $this->patternPrepared;
        }

        $replacements = [
            '\*\*' => '.*',
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\\\\\\[' => '[\[]',
            '\\\\\\]' => '[\]]',
        ];

        if ($this->delimiters === []) {
            $replacements += [
                '\*' => '.*',
                '\?' => '?',
            ];
        } else {
            $notDelimiters = '[^' . preg_quote(implode('', $this->delimiters), '#') . ']';
            $replacements += [
                '\*' => "$notDelimiters*",
                '\?' => $notDelimiters,
            ];
        }

        $replacements += [
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        ];

        $pattern = strtr(preg_quote($this->pattern, '#'), $replacements);
        $pattern = '#^' . $pattern . '$#us';

        if ($this->ignoreCase) {
            $pattern .= 'i';
        }

        $this->patternPrepared = $pattern;

        return $this->patternPrepared;
    }
}
