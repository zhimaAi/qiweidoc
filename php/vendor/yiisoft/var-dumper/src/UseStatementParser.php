<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper;

use RuntimeException;

use function array_slice;
use function defined;
use function file_get_contents;
use function is_array;
use function is_file;
use function is_readable;
use function mb_substr;
use function strpos;
use function strrchr;
use function substr;

/**
 * UseStatementParser given a PHP file, returns a set of `use` statements from the code.
 */
final class UseStatementParser
{
    /**
     * Returns a set of `use` statements from the code of the specified file.
     *
     * @param string $file File to read.
     *
     * @throws RuntimeException if there is a problem reading file.
     *
     * @return array<string, string> Use statements data.
     */
    public function fromFile(string $file): array
    {
        if (!is_file($file)) {
            throw new RuntimeException("File \"{$file}\" does not exist.");
        }

        if (!is_readable($file)) {
            throw new RuntimeException("File \"{$file}\" is not readable.");
        }

        $fileContent = file_get_contents($file);

        if ($fileContent === false) {
            throw new RuntimeException("Failed to read file \"{$file}\".");
        }

        $tokens = token_get_all($fileContent);
        array_shift($tokens);
        $uses = [];

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_USE && isset($tokens[$i + 2]) && $this->isTokenIsPartOfUse($tokens[$i + 2])) {
                $uses += $this->normalize(array_slice($tokens, $i + 1));
                continue;
            }
        }

        return $uses;
    }

    /**
     * Checks whether the token is part of the use statement data.
     *
     * @param array|string $token PHP token.
     *
     * @return bool Whether the token is part of the use statement data.
     */
    public function isTokenIsPartOfUse($token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        return $token[0] === T_STRING
            || $token[0] === T_NS_SEPARATOR
            || (defined('T_NAME_QUALIFIED') && $token[0] === T_NAME_QUALIFIED)
            || (defined('T_NAME_FULLY_QUALIFIED') && $token[0] === T_NAME_FULLY_QUALIFIED)
            || (defined('T_NAME_RELATIVE') && $token[0] === T_NAME_RELATIVE);
    }

    /**
     * Normalizes raw tokens into uniform use statement data.
     *
     * @param array<int, array<int, int|string>|string> $tokens Raw tokens.
     *
     * @return array<string, string> Normalized use statement data.
     */
    private function normalize(array $tokens): array
    {
        $commonNamespace = '\\';
        $current = '';
        $uses = [];

        foreach ($tokens as $token) {
            if ($this->isTokenIsPartOfUse($token)) {
                $current .= $token[1];
                continue;
            }
            if ($token === ',' || $token === ';') {
                if ($current !== '') {
                    $uses[] = $commonNamespace . $current;
                    $current = '';
                }
            }
            if ($token === ';') {
                break;
            }
            if ($token === '{') {
                $commonNamespace .= $current;
                $current = '';
                continue;
            }
            if ($token[0] === T_AS) {
                $current .= '@';
            }
        }

        return $this->replaceAliases($uses);
    }

    /**
     * Replaces aliases for the use statement data.
     *
     * @param string[] $uses Raw uses.
     *
     * @return array<string, string> Use statement data with the replaced aliases.
     */
    private function replaceAliases(array $uses): array
    {
        $result = [];

        foreach ($uses as $use) {
            $delimiterPosition = strpos($use, '@');

            if ($delimiterPosition !== false) {
                $alias = mb_substr($use, $delimiterPosition + 1);
                $result[$alias] = mb_substr($use, 0, $delimiterPosition);
                continue;
            }

            $part = strrchr($use, '\\');
            $result[$part === false ? $use : substr($part, 1)] = $use;
        }

        return $result;
    }
}
