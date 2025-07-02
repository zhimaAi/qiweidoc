<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Extractor;

use RuntimeException;

use function array_slice;
use function count;
use function in_array;
use function is_array;
use function is_string;

/**
 * Extracts translation keys from a string given.
 *
 * @psalm-type TranslatorToken = string|array{0: int, 1: string, 2: int}
 */
final class ContentParser
{
    private string $translatorCall = '->translate';

    /**
     * @psalm-var TranslatorToken[]
     */
    private array $translatorTokens = [];

    private int $translatorTokenCount = 0;

    private static array $brackets = [
        ')' => '(',
        ']' => '[',
        '}' => '{',
    ];

    private array $skippedLines = [];

    /**
     * @param string $defaultCategory Name of the category to use when no category is specified.
     * @param string|null $translator A string containing a method call that translates the message. If not specified,
     * "->translate" is assumed.
     */
    public function __construct(private string $defaultCategory, ?string $translator = null)
    {
        $this->setTranslator($translator ?? $this->translatorCall);
    }

    /**
     * @param string $content Code to extract translation keys from.
     *
     * @return array Extracted messages.
     */
    public function extract(string $content): array
    {
        $this->skippedLines = [];
        $tokens = token_get_all($content);

        return $this->extractMessagesFromTokens($tokens);
    }

    /**
     * @param string $defaultCategory Name of the category to use when no category is specified.
     */
    public function setDefaultCategory(string $defaultCategory): void
    {
        $this->defaultCategory = $defaultCategory;
    }

    /**
     * @return bool Whether there are skipped lines.
     */
    public function hasSkippedLines(): bool
    {
        return !empty($this->skippedLines);
    }

    /**
     * @return array Lines that were skipped during parsing.
     *
     * The format is:
     *
     * ```php
     * return [
     *     'fileName' => [
     *         [
     *             int $numberOfLine,
     *             string $incorrectLine,
     *         ],
     *     ],
     * ]
     * ```
     */
    public function getSkippedLines(): array
    {
        return $this->skippedLines;
    }

    private function setTranslator(string $translatorCall): void
    {
        $this->translatorCall = $translatorCall;
        $translatorTokens = token_get_all('<?php ' . $this->translatorCall);
        array_shift($translatorTokens);
        $this->translatorTokens = $translatorTokens;
        $this->translatorTokenCount = count($this->translatorTokens);

        if ($this->translatorTokenCount < 2) {
            throw new RuntimeException('Translator call cannot contain less than 2 tokens.');
        }
    }

    /**
     * @psalm-param array<integer, TranslatorToken> $tokens
     *
     * @psalm-suppress UnusedVariable See https://github.com/vimeo/psalm/issues/9080
     */
    private function extractMessagesFromTokens(array $tokens): array
    {
        $messages = $buffer = [];
        $matchedTokensCount = $pendingParenthesisCount = 0;
        $startTranslatorTokenIndex = 0;

        foreach ($tokens as $indexToken => $token) {
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT], true)) {
                continue;
            }

            if ($startTranslatorTokenIndex) {
                if ($this->tokensEqual($token, ')')) {
                    if ($pendingParenthesisCount === 0) {
                        $result = $this->extractParametersFromTokens($buffer);
                        if ($result === null) {
                            $skippedTokens = array_slice($tokens, $startTranslatorTokenIndex, $indexToken - $startTranslatorTokenIndex + 1);
                            $this->skippedLines[] = $this->getLinesData($skippedTokens);
                        } else {
                            $messages = array_merge_recursive($messages, $result);
                        }
                        $startTranslatorTokenIndex = 0;
                        $pendingParenthesisCount = 0;
                        $buffer = [];
                        continue;
                    }
                    $pendingParenthesisCount--;
                } elseif ($this->tokensEqual($token, '(')) {
                    $pendingParenthesisCount++;
                }
                $buffer[] = $token;
            } else {
                if ($matchedTokensCount === $this->translatorTokenCount) {
                    if ($this->tokensEqual($token, '(')) {
                        $startTranslatorTokenIndex = $indexToken - $this->translatorTokenCount;
                        continue;
                    }
                    $matchedTokensCount = 0;
                }

                if ($this->tokensEqual($token, $this->translatorTokens[$matchedTokensCount])) {
                    $matchedTokensCount++;
                } else {
                    $matchedTokensCount = 0;
                }
            }
        }

        return $messages;
    }

    /**
     * @psalm-param TranslatorToken[] $tokens
     */
    private function extractParametersFromTokens(array $tokens): ?array
    {
        $parameters = $this->splitTokensAsParams($tokens);

        if (!isset($parameters['id'])) {
            return null;
        }

        $messages = [$parameters['category'] ?? $this->defaultCategory => [$parameters['id']]];

        // Get translation messages from parameters
        if (isset($parameters['parameters'])) {
            $messages = array_merge_recursive($messages, $this->extractMessagesFromTokens($parameters['parameters']));
        }

        return $messages;
    }

    /**
     * @psalm-param TranslatorToken[] $tokens
     *
     * @psalm-return array{
     *     category?: null|string,
     *     id?: null|string,
     *     parameters?: null|list<TranslatorToken>
     * }
     */
    private function splitTokensAsParams(array $tokens): array
    {
        $parameters = [];
        $parameterIndex = 0;
        $commaStack = [];

        foreach ($tokens as $token) {
            if (empty($commaStack) && $token === ',') {
                $parameterIndex++;
                continue;
            }
            if (is_string($token)) {
                if (in_array($token, self::$brackets, true)) {
                    $commaStack[] = $token;
                } elseif (isset(self::$brackets[$token]) && array_pop($commaStack) !== self::$brackets[$token]) {
                    return [];
                }
            }
            $parameters[$parameterIndex][] = $token;
        }

        return [
            'id' => $this->getMessageStringFromTokens($parameters[0] ?? []),
            'parameters' => $parameters[1] ?? null,
            'category' => $this->getMessageStringFromTokens($parameters[2] ?? []),
        ];
    }

    /**
     * @psalm-param TranslatorToken[] $tokens
     */
    private function getMessageStringFromTokens(array $tokens): ?string
    {
        if (empty($tokens) || $tokens[0][0] !== T_CONSTANT_ENCAPSED_STRING) {
            return null;
        }

        $fullMessage = substr($tokens[0][1], 1, -1);

        $i = 1;
        $countTokens = count($tokens);
        while ($i < $countTokens && $tokens[$i] === '.') {
            if ($tokens[$i + 1][0] === T_CONSTANT_ENCAPSED_STRING) {
                $fullMessage .= substr($tokens[$i + 1][1], 1, -1);
            } elseif (in_array($tokens[$i + 1][0], [T_LNUMBER, T_DNUMBER], true)) {
                $fullMessage .= $tokens[$i + 1][1];
            } else {
                return null;
            }

            $i += 2;
        }

        return stripcslashes($fullMessage);
    }

    /**
     * Finds out if two PHP tokens are equal.
     *
     * @psalm-param TranslatorToken $a
     * @psalm-param TranslatorToken $b
     */
    private function tokensEqual(array|string $a, array|string $b): bool
    {
        if (is_string($a)) {
            return $a === $b;
        }

        return $a[0] === $b[0] && $a[1] === $b[1];
    }

    /**
     * @psalm-param TranslatorToken[] $tokens
     */
    private function getLinesData(array $tokens): array
    {
        $startLine = null;
        $codeLines = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($startLine === null) {
                    $startLine = $token[2];
                }
                $codeLines .= $token[1];
            } else {
                $codeLines .= $token;
            }
        }
        return [$startLine, $codeLines];
    }
}
