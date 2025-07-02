<?php

declare(strict_types=1);

namespace Yiisoft\Translator\Extractor;

use RuntimeException;
use Yiisoft\Files\FileHelper;
use Yiisoft\Files\PathMatcher\PathMatcher;

/**
 * Extracts translator IDs from files within a given path.
 */
final class TranslationExtractor
{
    private string $path;

    /** @var string[] */
    private array $only = ['**.php'];

    private array $skippedLines = [];

    /** @var string[] */
    private array $except = [
        '.svn',
        '.git',
        '.gitignore',
        '.gitkeep',
        '.hgignore',
        '.hgkeep',
        '/messages',
    ];

    /**
     * `TranslationExtractor` constructor.
     *
     * @param string $path Path to start extraction at.
     * @param string[]|null $only List of patterns that the files or directories should match. See {@see PathMatcher}.
     * @param string[]|null $except List of patterns that the files or directories should not match. See
     * {@see PathMatcher}.
     */
    public function __construct(string $path, ?array $only = null, ?array $except = null)
    {
        if (!is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist.', $path));
        }

        $this->path = $path;

        if ($only !== null) {
            $this->only = $only;
        }

        if ($except !== null) {
            $this->except = $except;
        }
    }

    /**
     * Extract messages.
     *
     * @param string $defaultCategory Category to use if category isn't set in translation call.
     * @param string|null $translatorCall Translation call to look for.
     *
     * @return array Extracted messages.
     */
    public function extract(string $defaultCategory = 'app', ?string $translatorCall = null): array
    {
        $messages = [];
        $parser = new ContentParser($defaultCategory, $translatorCall);

        $files = FileHelper::findFiles($this->path, [
            'filter' => (new PathMatcher())
                ->only(...$this->only)
                ->except(...$this->except),
            'recursive' => true,
        ]);

        foreach ($files as $file) {
            $fileContent = file_get_contents($file);
            $messages = array_merge_recursive($messages, $parser->extract($fileContent));
            if ($parser->hasSkippedLines()) {
                $this->skippedLines[$file] = $parser->getSkippedLines();
            }
        }

        return $messages;
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
}
