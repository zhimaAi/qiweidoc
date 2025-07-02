<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

use Exception;

use function sprintf;

/**
 * `CombinedRegexp` optimizes matching of multiple regular expressions.
 * Read more about the concept in
 * {@see https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html}.
 */
abstract class AbstractCombinedRegexp
{
    /**
     * @psalm-suppress MissingClassConstType
     */
    public const REGEXP_DELIMITER = '/';

    /**
     * @psalm-suppress MissingClassConstType
     */
    public const QUOTE_REPLACER = '\\/';

    /**
     * @return string[] Regular expressions to combine.
     */
    abstract public function getPatterns(): array;

    /**
     * @return string Flags to apply to all regular expressions.
     */
    abstract public function getFlags(): string;

    /**
     * @return string The compiled pattern.
     */
    abstract public function getCompiledPattern(): string;

    /**
     * Returns `true` whether the given string matches any of the patterns, `false` - otherwise.
     */
    abstract public function matches(string $string): bool;

    /**
     * Returns pattern that matches the given string.
     * @throws Exception if the string does not match any of the patterns.
     */
    abstract public function getMatchingPattern(string $string): string;

    /**
     * Returns position of the pattern that matches the given string.
     * @throws Exception if the string does not match any of the patterns.
     */
    abstract public function getMatchingPatternPosition(string $string): int;

    /**
     * @throws Exception
     * @return never-return
     */
    protected function throwFailedMatchException(string $string): void
    {
        throw new Exception(
            sprintf(
                'Failed to match pattern "%s" with string "%s".',
                $this->getCompiledPattern(),
                $string,
            )
        );
    }
}
