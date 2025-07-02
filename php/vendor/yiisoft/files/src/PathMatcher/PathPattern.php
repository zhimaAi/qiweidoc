<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatcher;

use Yiisoft\Strings\WildcardPattern;

/**
 * A shell path pattern to match against. Based on {@see WildcardPattern}.
 */
final class PathPattern implements PathMatcherInterface
{
    private const FILES = 1;
    private const DIRECTORIES = 2;

    private WildcardPattern $pattern;
    private ?int $matchOnly = null;

    /**
     * @param string $pattern The path pattern to match against.
     */
    public function __construct(string $pattern)
    {
        $this->pattern = (new WildcardPattern($pattern))->ignoreCase();
    }

    /**
     * Make pattern case sensitive.
     *
     * @return self
     */
    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->pattern = $this->pattern->ignoreCase(false);
        return $new;
    }

    /**
     * If path is not file or file not exists skip matching.
     *
     * @return self
     */
    public function onlyFiles(): self
    {
        $new = clone $this;
        $new->matchOnly = self::FILES;
        return $new;
    }

    /**
     * Skip matching if path is not directory or directory does no exist.
     *
     * @return self
     */
    public function onlyDirectories(): self
    {
        $new = clone $this;
        $new->matchOnly = self::DIRECTORIES;
        return $new;
    }

    /**
     * Checks if the passed path would match the given shell path pattern.
     * If need match only files and path is directory or conversely then matching skipped and returned `null`.
     *
     * @param string $path The tested path.
     *
     * @return bool|null Whether the path matches pattern or not, `null` if matching skipped.
     */
    public function match(string $path): ?bool
    {
        $path = str_replace('\\', '/', $path);

        if (
            ($this->matchOnly === self::FILES && is_dir($path)) ||
            ($this->matchOnly === self::DIRECTORIES && is_file($path))
        ) {
            return null;
        }

        return $this->pattern->match($path);
    }
}
