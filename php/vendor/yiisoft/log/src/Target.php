<?php

declare(strict_types=1);

namespace Yiisoft\Log;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Log\ContextProvider\CommonContextProvider;
use Yiisoft\Log\Message\CategoryFilter;
use Yiisoft\Log\Message\Formatter;

use function count;
use function in_array;
use function is_bool;
use function sprintf;

/**
 * Target is the base class for all log target classes.
 *
 * A log target object will filter the messages logged by {@see \Yiisoft\Log\Logger} according
 * to its {@see Target::setLevels()} and {@see Target::setCategories()}. It may also export
 * the filtered messages to specific destination defined by the target, such as emails, files.
 *
 * Level filter and category filter are combinatorial, i.e., only messages
 * satisfying both filter conditions will be handled. Additionally, you may
 * specify {@see Target::setExcept()} to exclude messages of certain categories.
 */
abstract class Target
{
    private CategoryFilter $categories;
    private Formatter $formatter;

    /**
     * @var Message[] The log messages.
     */
    private array $messages = [];

    /**
     * @var string[] The log message levels that this target is interested in.
     *
     * @see LogLevel See constants for valid level names.
     *
     * The value should be an array of level names.
     *
     * For example:
     *
     * ```php
     * ['error', 'warning'],
     * // or
     * [LogLevel::ERROR, LogLevel::WARNING]
     * ```
     *
     * Defaults is empty array, meaning all available levels.
     */
    private array $levels = [];

    /**
     * @var array The user parameters in the `key => value` format that should be logged in a each message.
     */
    private array $commonContext = [];

    /**
     * @var int How many log messages should be accumulated before they are exported.
     *
     * Defaults to 1000. Note that messages will always be exported when the application terminates.
     * Set this property to be 0 if you don't want to export messages until the application terminates.
     */
    private int $exportInterval = 1000;

    /**
     * @var bool|callable Enables or disables the current target to export.
     */
    private $enabled = true;

    /**
     * Exports log messages to a specific destination.
     * Child classes must implement this method.
     */
    abstract protected function export(): void;

    /**
     * When defining a constructor in child classes, you must call `parent::__construct()`.
     */
    public function __construct()
    {
        $this->categories = new CategoryFilter();
        $this->formatter = new Formatter();
    }

    /**
     * Processes the given log messages.
     *
     * This method will filter the given messages with levels and categories.
     * And if requested, it will also export the filtering result to specific medium (e.g. email).
     *
     * @param Message[] $messages Log messages to be processed.
     * @param bool $final Whether this method is called at the end of the current application.
     */
    public function collect(array $messages, bool $final): void
    {
        $this->filterMessages($messages);
        $count = count($this->messages);

        if ($count > 0 && ($final || ($this->exportInterval > 0 && $count >= $this->exportInterval))) {
            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            $this->export();
            $this->exportInterval = $oldExportInterval;
            $this->messages = [];
        }
    }

    /**
     * Sets a list of log message categories that this target is interested in.
     *
     * @param string[] $categories The list of log message categories.
     *
     * @throws InvalidArgumentException for invalid log message categories structure.
     *
     * @return self
     *
     * @see CategoryFilter::$include
     */
    public function setCategories(array $categories): self
    {
        $this->categories->include($categories);
        return $this;
    }

    /**
     * Sets a list of log message categories that this target is NOT interested in.
     *
     * @param string[] $except The list of log message categories.
     *
     * @throws InvalidArgumentException for invalid log message categories structure.
     *
     * @return self
     *
     * @see CategoryFilter::$exclude
     */
    public function setExcept(array $except): self
    {
        $this->categories->exclude($except);
        return $this;
    }

    /**
     * Sets a list of log message levels that current target is interested in.
     *
     * @param string[] $levels The list of log message levels.
     *
     * @throws InvalidArgumentException for invalid log message level.
     *
     * @return self
     *
     * @see Target::$levels
     */
    public function setLevels(array $levels): self
    {
        foreach ($levels as $key => $level) {
            Logger::assertLevelIsValid($level);
            $levels[$key] = $level;
        }

        $this->levels = $levels;
        return $this;
    }

    /**
     * Sets a user parameters in the `key => value` format that should be logged in a each message.
     *
     * @param array $commonContext The user parameters in the `key => value` format.
     *
     * @return self
     *
     * @see Target::$commonContext
     *
     * @deprecated since 2.1, to be removed in 3.0. Use {@see CommonContextProvider} instead.
     */
    public function setCommonContext(array $commonContext): self
    {
        $this->commonContext = $commonContext;
        return $this;
    }

    /**
     * Sets a PHP callable that returns a string representation of the log message.
     *
     * @param callable $format The PHP callable to get a string value from.
     *
     * @return self
     *
     * @see Formatter::$format
     */
    public function setFormat(callable $format): self
    {
        $this->formatter->setFormat($format);
        return $this;
    }

    /**
     * Sets a PHP callable that returns a string to be prefixed to every exported message.
     *
     * @param callable $prefix The PHP callable to get a string prefix of the log message.
     *
     * @return self
     *
     * @see Formatter::$prefix
     */
    public function setPrefix(callable $prefix): self
    {
        $this->formatter->setPrefix($prefix);
        return $this;
    }

    /**
     * Sets how many messages should be accumulated before they are exported.
     *
     * @param int $exportInterval The number of log messages to accumulate before exporting.
     *
     * @return self
     *
     * @see Target::$exportInterval
     */
    public function setExportInterval(int $exportInterval): self
    {
        $this->exportInterval = $exportInterval;
        return $this;
    }

    /**
     * Sets a date format for the log timestamp.
     *
     * @param string $format The date format for the log timestamp.
     *
     * @return self
     *
     * @see Target::$timestampFormat
     */
    public function setTimestampFormat(string $format): self
    {
        $this->formatter->setTimestampFormat($format);
        return $this;
    }

    /**
     * Sets a PHP callable that returns a boolean indicating whether this log target is enabled.
     *
     * The signature of the callable should be `function (): bool;`.
     *
     * @param callable $value The PHP callable to get a boolean value.
     *
     * @return self
     *
     * @see Target::$enabled
     */
    public function setEnabled(callable $value): self
    {
        $this->enabled = $value;
        return $this;
    }

    /**
     * Enables the log target.
     *
     * @return self
     *
     * @see Target::$enabled
     */
    public function enable(): self
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * Disables the log target.
     *
     * @return self
     *
     * @see Target::$enabled
     */
    public function disable(): self
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * Check whether the log target is enabled.
     *
     * @throws RuntimeException for a callable "enabled" that does not return a boolean.
     *
     * @return bool The value indicating whether this log target is enabled.
     *
     * @see Target::$enabled
     */
    public function isEnabled(): bool
    {
        if (is_bool($this->enabled)) {
            return $this->enabled;
        }

        if (!is_bool($enabled = ($this->enabled)())) {
            throw new RuntimeException(sprintf(
                'The PHP callable "enabled" must returns a boolean, %s received.',
                get_debug_type($enabled)
            ));
        }

        return $enabled;
    }

    /**
     * Gets a list of log messages that are retrieved from the logger so far by this log target.
     *
     * @return Message[] The list of log messages.
     */
    protected function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Gets a list of formatted log messages.
     *
     * @return string[] The list of formatted log messages.
     */
    protected function getFormattedMessages(): array
    {
        $formatted = [];

        foreach ($this->messages as $key => $message) {
            $formatted[$key] = $this->formatter->format($message, $this->commonContext);
        }

        return $formatted;
    }

    /**
     * Formats all log messages for display as a string.
     *
     * @param string $separator The log messages string separator.
     *
     * @return string The string formatted log messages.
     */
    protected function formatMessages(string $separator = ''): string
    {
        $formatted = '';

        foreach ($this->messages as $message) {
            $formatted .= $this->formatter->format($message, $this->commonContext) . $separator;
        }

        return $formatted;
    }

    /**
     * Gets a user parameters in the `key => value` format that should be logged in a each message.
     *
     * @return array The user parameters in the `key => value` format.
     *
     * @deprecated since 2.1, to be removed in 3.0. Use {@see CommonContextProvider} instead.
     */
    protected function getCommonContext(): array
    {
        return $this->commonContext;
    }

    /**
     * Filters the given messages according to their categories and levels.
     *
     * @param array $messages List log messages to be filtered.
     *
     * @throws InvalidArgumentException for non-instance Message.
     */
    private function filterMessages(array $messages): void
    {
        foreach ($messages as $i => $message) {
            if (!($message instanceof Message)) {
                throw new InvalidArgumentException('You must provide an instance of \Yiisoft\Log\Message.');
            }

            if (!empty($this->levels) && !in_array($message->level(), $this->levels, true)) {
                unset($messages[$i]);
                continue;
            }

            if ($this->categories->isExcluded($message->category())) {
                unset($messages[$i]);
                continue;
            }

            $this->messages[] = $message;
        }
    }
}
