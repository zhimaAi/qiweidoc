<?php

declare(strict_types=1);

namespace Yiisoft\Profiler;

/**
 * ProfilerInterface describes a profiler instance.
 */
interface ProfilerInterface
{
    /**
     * Marks the beginning of a code block for profiling.
     *
     * This has to be matched with a call to {@see end()} with the same category name.
     * The begin- and end- calls must also be properly nested.
     *
     * @param string $token Token for the code block.
     * @param array $context The context data of this profile block.
     */
    public function begin(string $token, array $context = []): void;

    /**
     * Marks the end of a code block for profiling.
     *
     * This has to be matched with a previous call to {@see begin()} with the same category name.
     *
     * @param string $token Token for the code block.
     * @param array $context The context data of this profile block.
     *
     * {@see begin()}
     */
    public function end(string $token, array $context = []): void;

    /**
     * Find messages with a given token.
     *
     * @param string $token Code block token.
     *
     * @return Message[] The messages profiler.
     */
    public function findMessages(string $token): array;

    /**
     * Flushes profiling messages from memory to actual storage.
     */
    public function flush(): void;
}
