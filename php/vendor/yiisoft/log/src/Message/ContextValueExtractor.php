<?php

declare(strict_types=1);

namespace Yiisoft\Log\Message;

use function array_key_exists;
use function count;
use function is_array;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final class ContextValueExtractor
{
    /**
     * @psalm-return array{0:bool,1:mixed}
     */
    public static function extract(array $context, string $key): array
    {
        $path = self::parsePath($key);

        $lastKey = array_pop($path);
        $array = $context;

        foreach ($path as $pathItem) {
            $array = $array[$pathItem] ?? null;
            if (!is_array($array)) {
                return [false, null];
            }
        }

        return array_key_exists($lastKey, $array)
            ? [true, $array[$lastKey]]
            : [false, null];
    }

    /**
     * @psalm-return non-empty-list<string>
     */
    private static function parsePath(string $path): array
    {
        if ($path === '') {
            return [''];
        }

        if (!str_contains($path, '.')) {
            return [str_replace('\\\\', '\\', $path)];
        }

        /** @psalm-var non-empty-list<array{0:string, 1:int}> $matches */
        $matches = preg_split(
            sprintf(
                '/(?<!%1$s)((?>%1$s%1$s)*)%2$s/',
                preg_quote('\\', '/'),
                preg_quote('.', '/')
            ),
            $path,
            -1,
            PREG_SPLIT_OFFSET_CAPTURE
        );
        $result = [];
        $countResults = count($matches);
        for ($i = 1; $i < $countResults; $i++) {
            $l = $matches[$i][1] - $matches[$i - 1][1] - strlen($matches[$i - 1][0]) - 1;
            $result[] = $matches[$i - 1][0] . ($l > 0 ? str_repeat('\\', $l) : '');
        }
        $result[] = $matches[$countResults - 1][0];

        return array_map(
            static fn(string $key): string => str_replace(
                [
                    '\\\\',
                    '\\.',
                ],
                [
                    '\\',
                    '.',
                ],
                $key
            ),
            $result
        );
    }
}
