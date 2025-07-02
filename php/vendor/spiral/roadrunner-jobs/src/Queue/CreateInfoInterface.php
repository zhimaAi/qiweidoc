<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Queue;

/**
 * The generic interface meaning that each implementation is valid for creating
 * new queues.
 *
 * @psalm-type CreateInfoArrayType = array {
 *  name: non-empty-string,
 *  driver: value-of<Driver>,
 *  priority: positive-int,
 *  ...
 * }
 */
interface CreateInfoInterface extends \JsonSerializable
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    public function getDriver(): Driver;

    /**
     * When transferring to the internal RPC method of creating queues, the data
     * must be represented in the form of a Map<string, string> type, which can
     * be represented as PHP array<non-empty-string, non-empty-string>.
     *
     * This method returns all available settings in the queues in the specified
     * format.
     *
     * @return CreateInfoArrayType
     */
    public function toArray(): array;
}
