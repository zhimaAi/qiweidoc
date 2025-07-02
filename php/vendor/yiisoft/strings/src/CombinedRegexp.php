<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

use Exception;
use InvalidArgumentException;

use function array_values;
use function count;
use function implode;
use function preg_match;
use function str_repeat;
use function strtr;

/**
 * `CombinedRegexp` optimizes matching of multiple regular expressions.
 * Read more about the concept in
 * {@see https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html}.
 */
final class CombinedRegexp extends AbstractCombinedRegexp
{
    /**
     * @var string[]
     */
    private array $patterns;
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $compiledPattern;

    /**
     * @param string[] $patterns Regular expressions to combine.
     * @param string $flags Flags to apply to all regular expressions.
     */
    public function __construct(
        array $patterns,
        private readonly string $flags = ''
    ) {
        if (empty($patterns)) {
            throw new InvalidArgumentException('At least one pattern should be specified.');
        }

        $this->patterns = array_values($patterns);
        $this->compiledPattern = $this->compilePatterns($this->patterns) . $this->flags;
    }

    /**
     * @return string The compiled pattern.
     */
    public function getCompiledPattern(): string
    {
        return $this->compiledPattern;
    }

    /**
     * Returns `true` whether the given string matches any of the patterns, `false` - otherwise.
     */
    public function matches(string $string): bool
    {
        return preg_match($this->compiledPattern, $string) === 1;
    }

    /**
     * Returns pattern that matches the given string.
     * @throws Exception if the string does not match any of the patterns.
     */
    public function getMatchingPattern(string $string): string
    {
        return $this->patterns[$this->getMatchingPatternPosition($string)];
    }

    /**
     * Returns position of the pattern that matches the given string.
     * @throws Exception if the string does not match any of the patterns.
     */
    public function getMatchingPatternPosition(string $string): int
    {
        $match = preg_match($this->compiledPattern, $string, $matches);
        if ($match !== 1) {
            $this->throwFailedMatchException($string);
        }

        return count($matches) - 1;
    }

    /**
     * @param string[] $patterns
     *
     * @psalm-param list<string> $patterns
     * @psalm-return non-empty-string
     */
    private function compilePatterns(array $patterns): string
    {
        $quotedPatterns = [];

        /**
         * Possible mutant escaping, but it's ok for our case.
         * It doesn't matter where to place `()` in the pattern:
         * https://regex101.com/r/lE1Q1S/1, https://regex101.com/r/rWg7Fj/1
         */
        foreach ($patterns as $i => $pattern) {
            $quotedPatterns[] = $pattern . str_repeat('()', $i);
        }

        $combinedRegexps = '(?|' . strtr(
            implode('|', $quotedPatterns),
            [self::REGEXP_DELIMITER => self::QUOTE_REPLACER]
        ) . ')';

        return self::REGEXP_DELIMITER . $combinedRegexps . self::REGEXP_DELIMITER;
    }

    public function getPatterns(): array
    {
        return $this->patterns;
    }

    public function getFlags(): string
    {
        return $this->flags;
    }
}
