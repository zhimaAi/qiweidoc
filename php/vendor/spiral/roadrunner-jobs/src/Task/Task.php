<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

/**
 * @psalm-immutable
 * @psalm-allow-private-mutation
 */
abstract class Task implements TaskInterface
{
    use HeadersTrait;

    /**
     * @param non-empty-string $name
     * @param array<non-empty-string, array<string>> $headers
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string|\Stringable $payload,
        array $headers = [],
    ) {
        \assert($this->name !== '', 'Precondition [job !== ""] failed');

        $this->headers = $headers;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): string
    {
        return (string)$this->payload;
    }
}
