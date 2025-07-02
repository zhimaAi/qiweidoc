<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatcher;

/**
 * Composite matcher allows combining several matchers.
 */
final class CompositeMatcher implements PathMatcherInterface
{
    private bool $matchAny;

    /**
     * @var PathMatcherInterface[]
     */
    private array $matchers;

    private function __construct(bool $matchAny, PathMatcherInterface ...$matchers)
    {
        $this->matchers = $matchers;
        $this->matchAny = $matchAny;
    }

    /**
     * Get an instance of composite matcher that gives a match if any of sub-matchers match.
     *
     * @param PathMatcherInterface ...$matchers Matchers to check.
     *
     * @return static
     */
    public static function any(PathMatcherInterface ...$matchers): self
    {
        return new self(true, ...$matchers);
    }

    /**
     * Get an instance of composite matcher that gives a match only if all of sub-matchers match.
     *
     * @param PathMatcherInterface ...$matchers Matchers to check.
     *
     * @return static
     */
    public static function all(PathMatcherInterface ...$matchers): self
    {
        return new self(false, ...$matchers);
    }

    public function match(string $path): ?bool
    {
        $allNulls = true;

        foreach ($this->matchers as $matcher) {
            $match = $matcher->match($path);

            if ($match === null) {
                continue;
            }

            $allNulls = false;

            if ($this->matchAny && $match) {
                return true;
            }

            if (!$this->matchAny && !$match) {
                return false;
            }
        }

        return $allNulls ? null : !$this->matchAny;
    }
}
