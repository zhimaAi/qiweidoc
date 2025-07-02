<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Helper;

use Yiisoft\Strings\StringHelper;

use function in_array;

/**
 * `BacktraceMatcher` provides methods to match backtrace items returned by the PHP function `debug_backtrace()`.
 *
 * @see https://www.php.net/manual/function.debug-backtrace.php
 *
 * @psalm-type TBacktraceItem = array{
 *     file?: string,
 *     line?: int,
 *     function?: string,
 *     class?: class-string,
 *     object?: object,
 *     type?: string,
 *     args?:array,
 * }
 */
final class BacktraceMatcher
{
    /**
     * @param string[] $patterns
     * @psalm-param TBacktraceItem $backtraceItem
     */
    public static function matchesFile(array $backtraceItem, array $patterns): bool
    {
        $path = $backtraceItem['file'] ?? null;
        return $path !== null && StringHelper::matchAnyRegex($path, $patterns);
    }

    /**
     * @param string[] $classes
     * @psalm-param TBacktraceItem $backtraceItem
     */
    public static function matchesClass(array $backtraceItem, array $classes): bool
    {
        $class = $backtraceItem['class'] ?? null;
        return $class !== null && in_array($class, $classes, true);
    }
}
