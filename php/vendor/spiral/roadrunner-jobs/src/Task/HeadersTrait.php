<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

/**
 * @mixin ProvidesHeadersInterface
 * @psalm-require-implements ProvidesHeadersInterface
 * @psalm-immutable
 */
trait HeadersTrait
{
    /**
     * @var array<non-empty-string, array<string>>
     */
    protected array $headers = [];

    /**
     * @return array<non-empty-string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param non-empty-string $name Header field name.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]) && \count($this->headers[$name]) > 0;
    }

    /**
     * @param non-empty-string $name
     */
    public function getHeaderLine(string $name): string
    {
        return \implode(',', $this->getHeader($name));
    }

    /**
     * @param non-empty-string $name
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }
}
