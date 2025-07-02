<?php

declare(strict_types=1);

namespace Yiisoft\Strings;

/**
 * `MemoizedCombinedRegexp` is a decorator for {@see AbstractCombinedRegexp} that caches results of
 * - {@see AbstractCombinedRegexp::matches()}
 * - {@see AbstractCombinedRegexp::getMatchingPattern()}
 * - {@see AbstractCombinedRegexp::getMatchingPatternPosition()}.
 */
final class MemoizedCombinedRegexp extends AbstractCombinedRegexp
{
    /**
     * @var array<string, array{matches:bool, position?:int}>
     */
    private array $results = [];

    public function __construct(
        private readonly AbstractCombinedRegexp $decorated,
    ) {
    }

    public function getCompiledPattern(): string
    {
        return $this->decorated->getCompiledPattern();
    }

    public function matches(string $string): bool
    {
        $this->evaluate($string);

        return $this->results[$string]['matches'];
    }

    public function getMatchingPattern(string $string): string
    {
        $this->evaluate($string);

        return $this->getPatterns()[$this->getMatchingPatternPosition($string)];
    }

    public function getMatchingPatternPosition(string $string): int
    {
        $this->evaluate($string);

        return $this->results[$string]['position'] ?? $this->throwFailedMatchException($string);
    }

    private function evaluate(string $string): void
    {
        if (isset($this->results[$string])) {
            return;
        }
        try {
            $position = $this->decorated->getMatchingPatternPosition($string);

            $this->results[$string]['matches'] = true;
            $this->results[$string]['position'] = $position;
        } catch (\Exception) {
            $this->results[$string]['matches'] = false;
        }
    }

    public function getPatterns(): array
    {
        return $this->decorated->getPatterns();
    }

    public function getFlags(): string
    {
        return $this->decorated->getFlags();
    }
}
