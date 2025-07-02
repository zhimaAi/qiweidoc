<?php

declare(strict_types=1);

namespace Yiisoft\Translator;

use InvalidArgumentException;

use function count;
use function in_array;
use function is_int;

class SimpleMessageFormatter implements MessageFormatterInterface
{
    /**
     * @psalm-suppress MissingClassConstType
     */
    private const PLURAL_ONE = 'one';

    /**
     * @psalm-suppress MissingClassConstType
     */
    private const PLURAL_OTHER = 'other';

    /**
     * @psalm-suppress MissingClassConstType
     */
    private const PLURAL_KEYS = [self::PLURAL_ONE, self::PLURAL_OTHER];

    public function format(string $message, array $parameters, string $locale = 'en-US'): string
    {
        preg_match_all('/{((?>[^{}]+)|(?R))*}/', $message, $matches);
        $replacements = [];

        foreach ($matches[0] as $match) {
            $parts = explode(',', $match);
            $parameter = trim($parts[0], '{} ');

            if ($parameter === '') {
                throw new InvalidArgumentException('Parameter\'s name can not be empty.');
            }

            if (!array_key_exists($parameter, $parameters)) {
                throw new InvalidArgumentException("\"$parameter\" parameter's value is missing.");
            }

            $value = $parameters[$parameter] ?? '';

            if (!is_scalar($value)) {
                continue;
            }

            if (count($parts) === 1) {
                $replacements[$match] = $value;

                continue;
            }

            $format = ltrim($parts[1]);
            $format = rtrim($format, '}');

            switch ($format) {
                case 'plural':
                    $options = $parts[2] ?? '';
                    $replacements[$match] = self::pluralize($value, $options);

                    break;
                default:
                    $replacements[$match] = $value;
            }
        }

        return strtr($message, $replacements);
    }

    private static function pluralize(mixed $value, string $options): string
    {
        if (!$options) {
            throw new InvalidArgumentException('Missing plural keys: ' . self::formatList(self::PLURAL_KEYS) . '.');
        }

        if (!is_int($value)) {
            throw new InvalidArgumentException('Only integer numbers are supported with plural format.');
        }

        preg_match_all('/([^{}\s]+)({(.*?)})/', $options, $pluralMatches);

        $map = [];
        foreach ($pluralMatches[1] as $index => $match) {
            if (!in_array($match, self::PLURAL_KEYS, true)) {
                continue;
            }

            $map[$match] = $pluralMatches[3][$index];
        }

        if (!isset($map[self::PLURAL_ONE])) {
            throw new InvalidArgumentException('Missing plural key "' . self::PLURAL_ONE . '".');
        }

        if (!isset($map[self::PLURAL_OTHER])) {
            throw new InvalidArgumentException('Missing plural key "' . self::PLURAL_OTHER . '".');
        }

        return $value === 1 ? $map[self::PLURAL_ONE] : $map[self::PLURAL_OTHER];
    }

    /**
     * @param string[] $items
     */
    private static function formatList(array $items): string
    {
        return implode(', ', array_map(fn (string $value): string => '"' . $value . '"', $items));
    }
}
