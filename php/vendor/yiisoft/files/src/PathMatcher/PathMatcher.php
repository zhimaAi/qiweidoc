<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatcher;

use Yiisoft\Strings\StringHelper;

/**
 * Path matcher is based on {@see PathPattern} with the following logic:
 *
 *  1. Process `only()`. If there is at least one match, then continue, else return `false`;
 *  2. Process `except()`. If there is at least one match, return `false`, else continue;
 *  3. Process `callback()`. If there is at least one not match, return `false`, else return `true`.
 *
 * Either implementations of {@see PathMatcherInterface} or strings could be used in all above. They will be converted
 * {@see PathPattern} according to the options.
 *
 * If the string ends in `/`, then {@see PathPattern} will be created with {@see PathPattern::onlyDirectories()} option.
 * Else it will be create with {@see PathPattern::onlyFiles()} option. You can disable this behavior using
 * {@see PathMatcher::notCheckFilesystem()}.
 *
 * There are several other options available:
 *
 *  - {@see PathMatcher::caseSensitive()} makes string patterns case sensitive;
 *  - {@see PathMatcher::withFullPath()} string patterns will be matched as full path, not just as ending of the path;
 *  - {@see PathMatcher::withNotExactSlashes()} match `/` character with wildcards in string patterns.
 *
 * Usage example:
 *
 * ```php
 * $matcher = (new PathMatcher())
 *     ->notCheckFilesystem()
 *     ->only('*.css', '*.js')
 *     ->except('theme.css');
 *
 * $matcher->match('/var/www/example.com/assets/css/main.css'); // true
 * $matcher->match('/var/www/example.com/assets/css/main.css.map'); // false
 * $matcher->match('/var/www/example.com/assets/css/theme.css'); // false
 * ```
 */
final class PathMatcher implements PathMatcherInterface
{
    /**
     * @var PathMatcherInterface[]|null
     */
    private ?array $only = null;

    /**
     * @var PathMatcherInterface[]|null
     */
    private ?array $except = null;

    /**
     * @var callable[]|null
     */
    private ?array $callbacks = null;

    private bool $caseSensitive = false;
    private bool $checkFilesystem = true;

    /**
     * Make string patterns case sensitive.
     * Note: applies only to string patterns.
     *
     * @return self
     */
    public function caseSensitive(): self
    {
        $new = clone $this;
        $new->caseSensitive = true;
        return $new;
    }

    /**
     * Match path only as string, do not check if file or directory exists.
     * Note: applies only to string patterns.
     *
     * @return self
     */
    public function doNotCheckFilesystem(): self
    {
        $new = clone $this;
        $new->checkFilesystem = false;
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should match.
     *
     * @param PathMatcherInterface|string ...$patterns
     *
     * @return self
     */
    public function only(...$patterns): self
    {
        $new = clone $this;
        $new->only = $this->prepareMatchers($patterns);
        return $new;
    }

    /**
     * Set list of patterns that the files or directories should not match.
     *
     * @see https://github.com/yiisoft/strings#wildcardpattern-usage
     *
     * @param PathMatcherInterface|string ...$patterns Simple POSIX-style string matching.
     *
     * @return self
     */
    public function except(...$patterns): self
    {
        $new = clone $this;
        $new->except = $this->prepareMatchers($patterns);
        return $new;
    }

    /**
     * Set list of PHP callbacks that are called for each path.
     *
     * The signature of the callback should be: `function ($path)`, where `$path` refers the full path to be filtered.
     * The callback should return `true` if there is a match and `false` otherwise.
     *
     * @param callable ...$callbacks
     *
     * @return self
     */
    public function callback(callable ...$callbacks): self
    {
        $new = clone $this;
        $new->callbacks = $callbacks;
        return $new;
    }

    /**
     * Checks if the passed path match specified conditions.
     *
     * @param string $path The tested path.
     *
     * @return bool Whether the path matches conditions or not.
     */
    public function match(string $path): bool
    {
        if (!$this->matchOnly($path)) {
            return false;
        }

        if ($this->matchExcept($path)) {
            return false;
        }

        if ($this->callbacks !== null) {
            foreach ($this->callbacks as $callback) {
                if (!$callback($path)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function matchOnly(string $path): bool
    {
        if ($this->only === null) {
            return true;
        }

        $hasFalse = false;
        $hasNull = false;

        foreach ($this->only as $pattern) {
            if ($pattern->match($path) === true) {
                return true;
            }
            if ($pattern->match($path) === false) {
                $hasFalse = true;
            }
            if ($pattern->match($path) === null) {
                $hasNull = true;
            }
        }

        if ($this->checkFilesystem) {
            if (is_file($path)) {
                return !$hasFalse;
            }
            if (is_dir($path)) {
                return $hasNull;
            }
        }

        return false;
    }

    private function matchExcept(string $path): bool
    {
        if ($this->except === null) {
            return false;
        }

        foreach ($this->except as $pattern) {
            if ($pattern->match($path) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param PathMatcherInterface[]|string[] $patterns
     *
     * @return PathMatcherInterface[]
     */
    private function prepareMatchers(array $patterns): array
    {
        $pathPatterns = [];
        foreach ($patterns as $pattern) {
            if ($pattern instanceof PathMatcherInterface) {
                $pathPatterns[] = $pattern;
                continue;
            }

            $pattern = strtr($pattern, '/\\', '//');

            $isDirectoryPattern = str_ends_with($pattern, '/');
            if ($isDirectoryPattern) {
                $pattern = StringHelper::substring($pattern, 0, -1);
            }

            $pathPattern = new PathPattern($pattern);

            if ($this->caseSensitive) {
                $pathPattern = $pathPattern->caseSensitive();
            }

            if ($this->checkFilesystem) {
                $pathPattern = $isDirectoryPattern
                    ? $pathPattern->onlyDirectories()
                    : $pathPattern->onlyFiles();
            }

            $pathPatterns[] = $pathPattern;
        }
        return $pathPatterns;
    }
}
