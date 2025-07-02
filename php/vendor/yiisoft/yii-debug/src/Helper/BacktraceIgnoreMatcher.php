<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Helper;

use Yiisoft\Strings\CombinedRegexp;
use Yiisoft\Yii\Debug\Debugger;

/**
 * All backtrace parameters should contain at least 4 elements in the following order:
 * 0 – Called method
 * 1 – Proxy
 * 2 – Real using place / Composer\ClassLoader include function
 * 3 – Whatever / Composer\ClassLoader
 *
 * @psalm-import-type BacktraceType from Debugger
 */
final class BacktraceIgnoreMatcher
{
    /**
     * @param string[] $patterns
     * @psalm-param BacktraceType $backtrace
     */
    public static function isIgnoredByFile(array $backtrace, array $patterns): bool
    {
        if (!isset($backtrace[2]['file'])) {
            return false;
        }
        $path = $backtrace[2]['file'];

        return self::doesStringMatchPattern($path, $patterns);
    }

    /**
     * @psalm-param BacktraceType $backtrace
     */
    public static function isIgnoredByClass(array $backtrace, array $classes): bool
    {
        return isset($backtrace[3]['class']) && in_array($backtrace[3]['class'], $classes, true);
    }

    /**
     * @param string[] $patterns
     */
    public static function doesStringMatchPattern(string $string, array $patterns): bool
    {
        if (empty($patterns)) {
            return false;
        }
        return (new CombinedRegexp($patterns))->matches($string);
    }
}
