<?php

declare(strict_types=1);

namespace Yiisoft\Log\ContextProvider;

use InvalidArgumentException;
use Yiisoft\Log\Message;

/**
 * @psalm-import-type TraceItem from Message
 */
final class SystemContextProvider implements ContextProviderInterface
{
    /**
     * @var string[] $excludedTracePaths Array of paths to exclude from tracing when tracing is enabled.
     */
    private array $excludedTracePaths;

    /**
     * @param int $traceLevel How much call stack information (file name and line number) should be logged for each
     * log message. If it is greater than 0, at most that number of call stacks will be logged. Note that only
     * application call stacks are counted.
     * @param string[] $excludedTracePaths Array of paths to exclude from tracing when tracing is enabled
     * with {@see $traceLevel}.
     */
    public function __construct(
        private int $traceLevel = 0,
        array $excludedTracePaths = [],
    ) {
        /** @psalm-suppress DeprecatedMethod `setExcludedTracePaths` will be private and not deprecated */
        $this->setExcludedTracePaths($excludedTracePaths);
    }

    public function getContext(): array
    {
        /** @psalm-var list<TraceItem> $trace */
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace);
        return [
            'time' => microtime(true),
            'trace' => $this->collectTrace($trace),
            'memory' => memory_get_usage(),
            'category' => Message::DEFAULT_CATEGORY,
        ];
    }

    /**
     * Sets how much call stack information (file name and line number) should be logged for each log message.
     *
     * @param int $traceLevel The number of call stack information.
     *
     * @see self::$traceLevel
     *
     * @deprecated since 2.1.0, to be removed in 3.0.0. Use constructor parameter "traceLevel" instead.
     */
    public function setTraceLevel(int $traceLevel): self
    {
        $this->traceLevel = $traceLevel;
        return $this;
    }

    /**
     * Sets an array of paths to exclude from tracing when tracing is enabled with {@see self::$traceLevel}.
     *
     * @param string[] $excludedTracePaths The paths to exclude from tracing.
     *
     * @throws InvalidArgumentException for non-string values.
     *
     * @see self::$excludedTracePaths
     *
     * @deprecated since 2.1.0, to be removed in 3.0.0. Use constructor parameter "excludedTracePaths" instead.
     */
    public function setExcludedTracePaths(array $excludedTracePaths): self
    {
        foreach ($excludedTracePaths as $excludedTracePath) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!is_string($excludedTracePath)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The trace path must be a string, %s received.',
                        get_debug_type($excludedTracePath)
                    )
                );
            }
        }

        $this->excludedTracePaths = $excludedTracePaths;
        return $this;
    }

    /**
     * Collects a trace when tracing is enabled with {@see Logger::setTraceLevel()}.
     *
     * @param array[] $backtrace The list of call stack information.
     * @psalm-param list<TraceItem> $backtrace
     *
     * @return array[] Collected a list of call stack information.
     * @psalm-return list<TraceItem>
     */
    private function collectTrace(array $backtrace): array
    {
        $traces = [];

        if ($this->traceLevel > 0) {
            $count = 0;

            foreach ($backtrace as $trace) {
                if (isset($trace['file'], $trace['line'])) {
                    $excludedMatch = array_filter(
                        $this->excludedTracePaths,
                        static fn($path) => str_contains($trace['file'], $path)
                    );

                    if (empty($excludedMatch)) {
                        $traces[] = $trace;
                        if (++$count >= $this->traceLevel) {
                            break;
                        }
                    }
                }
            }
        }

        return $traces;
    }
}
