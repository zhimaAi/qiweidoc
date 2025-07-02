<?php

declare(strict_types=1);

namespace Yiisoft\Files\PathMatcher;

/**
 * An interface implemented by objects that perform matching of paths.
 */
interface PathMatcherInterface
{
    /**
     * Checks if the path matches.
     *
     * @param string $path The tested path.
     *
     * @return bool|null Whether the path matches or not, `null` if matching skipped.
     */
    public function match(string $path): ?bool;
}
