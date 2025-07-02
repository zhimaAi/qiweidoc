<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper;

use Closure;
use ReflectionException;
use ReflectionFunction;

use function array_filter;
use function array_pop;
use function array_shift;
use function array_slice;
use function explode;
use function file;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function token_get_all;
use function trim;

/**
 * ClosureExporter exports PHP {@see \Closure} as a string containing PHP code.
 *
 * The string is a valid PHP expression that can be evaluated by PHP parser
 * and the evaluation result will give back the closure instance.
 */
final class ClosureExporter
{
    private UseStatementParser $useStatementParser;

    public function __construct()
    {
        $this->useStatementParser = new UseStatementParser();
    }

    /**
     * Export closure as a string containing PHP code.
     *
     * @param Closure $closure Closure to export.
     * @param int $level Level for padding.
     *
     * @throws ReflectionException
     *
     * @return string String containing PHP code.
     */
    public function export(Closure $closure, int $level = 0): string
    {
        $reflection = new ReflectionFunction($closure);

        $fileName = $reflection->getFileName();
        $start = $reflection->getStartLine();
        $end = $reflection->getEndLine();

        if ($fileName === false || $start === false || $end === false || ($fileContent = file($fileName)) === false) {
            return 'function () {/* Error: unable to determine Closure source */}';
        }

        --$start;
        $uses = $this->useStatementParser->fromFile($fileName);
        $tokens = token_get_all('<?php ' . implode('', array_slice($fileContent, $start, $end - $start)));
        array_shift($tokens);

        $bufferUse = '';
        $closureTokens = [];
        $pendingParenthesisCount = 0;

        /** @var int<1, max> $i */
        foreach ($tokens as $i => $token) {
            if (in_array($token[0], [T_FUNCTION, T_FN, T_STATIC], true)) {
                $closureTokens[] = $token[1];
                continue;
            }

            if ($closureTokens === []) {
                continue;
            }

            $readableToken = is_array($token) ? $token[1] : $token;

            if ($this->useStatementParser->isTokenIsPartOfUse($token)) {
                if ($this->isUseConsistingOfMultipleParts($readableToken)) {
                    $readableToken = $this->processFullUse($readableToken, $uses);
                    $bufferUse = '';
                } elseif (isset($uses[$readableToken])) {
                    if (isset($tokens[$i + 1]) && $this->useStatementParser->isTokenIsPartOfUse($tokens[$i + 1])) {
                        $bufferUse .= $uses[$readableToken];
                        continue;
                    }
                    $readableToken = $uses[$readableToken];
                } elseif ($readableToken === '\\' && isset($tokens[$i - 1][1]) && $tokens[$i - 1][1] === '\\') {
                    continue;
                } elseif (isset($tokens[$i + 1]) && $this->useStatementParser->isTokenIsPartOfUse($tokens[$i + 1])) {
                    $bufferUse .= $readableToken;
                    continue;
                }
                if (!empty($bufferUse)) {
                    if ($bufferUse !== $readableToken && !str_contains($readableToken, $bufferUse)) {
                        $readableToken = $bufferUse . $readableToken;
                    }
                    $bufferUse = '';
                }
            }

            if (is_string($token)) {
                if ($this->isOpenParenthesis($token)) {
                    $pendingParenthesisCount++;
                } elseif ($this->isCloseParenthesis($token)) {
                    if ($pendingParenthesisCount === 0) {
                        break;
                    }
                    --$pendingParenthesisCount;
                } elseif ($token === ',' || $token === ';') {
                    if ($pendingParenthesisCount === 0) {
                        break;
                    }
                }
            }

            $closureTokens[] = $readableToken;
        }

        return $this->formatClosure(implode('', $closureTokens), $level);
    }

    private function formatClosure(string $code, int $level): string
    {
        $code = explode("\n", $code);
        $fistLine = array_shift($code);
        $minimumIndent = null;
        for ($i = 1; $i <= count($code) - 1; $i++) {
            $indent = strspn($code[$i], ' ');
            if ($indent === mb_strlen($code[$i])) {
                continue;
            }
            if ($minimumIndent === null || $indent < $minimumIndent) {
                $minimumIndent = $indent;
            }
        }
        if ($minimumIndent > 0) {
            foreach ($code as $index => $line) {
                $code[$index] = mb_substr($line, $minimumIndent);
            }
        }

        $spaces = $level <= 1 ? '' : str_repeat(' ', ($level - 1) * 4);
        return implode("\n" . $spaces, [$fistLine, ...$code]);
    }

    /**
     * Returns the last part from the use statement data.
     *
     * @param string $use The full use statement data.
     *
     * @return string The last part from the use statement data.
     */
    private function getUseLastPart(string $use): string
    {
        $parts = array_filter(explode('\\', $use));
        return (string) array_pop($parts);
    }

    /**
     * Processes and returns the full use statement data.
     *
     * @param string $use The use statement data to process.
     * @param array<string, string> $uses The use statement data.
     *
         * @return string The processed full use statement.
     */
    private function processFullUse(string $use, array $uses): string
    {
        $lastPart = $this->getUseLastPart($use);

        if (isset($uses[$lastPart])) {
            return $uses[$lastPart];
        }

        $result = '';

        do {
            $lastPart = $this->getUseLastPart($use);
            $use = mb_substr($use, 0, -mb_strlen("\\{$lastPart}"));
            $result = ($uses[$lastPart] ?? $lastPart) . '\\' . $result;
        } while (!empty($lastPart) && !isset($uses[$lastPart]));

        return '\\' . trim($result, '\\');
    }

    /**
     * Checks whether the use statement data consists of multiple parts.
     *
     * @param string $use The use statement data.
     *
     * @return bool Whether the use statement data consists of multiple parts.
     */
    private function isUseConsistingOfMultipleParts(string $use): bool
    {
        return $use !== '\\' && str_contains($use, '\\');
    }

    /**
     * Checks whether the value of the token is an opening parenthesis.
     *
     * @param string $value The token value.
     *
     * @return bool Whether the value of the token is an opening parenthesis.
     */
    private function isOpenParenthesis(string $value): bool
    {
        return in_array($value, ['{', '[', '(']);
    }

    /**
     * Checks whether the value of the token is a closing parenthesis.
     *
     * @param string $value The token value.
     *
     * @return bool Whether the value of the token is a closing parenthesis.
     */
    private function isCloseParenthesis(string $value): bool
    {
        return in_array($value, ['}', ']', ')']);
    }
}
