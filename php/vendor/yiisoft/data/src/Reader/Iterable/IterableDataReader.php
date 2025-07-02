<?php

declare(strict_types=1);

namespace Yiisoft\Data\Reader\Iterable;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\DataReaderException;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\FilterHandlerInterface;
use Yiisoft\Data\Reader\FilterInterface;
use Yiisoft\Data\Reader\Iterable\FilterHandler\AllHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\AnyHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\BetweenHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\EqualsHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\EqualsEmptyHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\EqualsNullHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\GreaterThanHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\GreaterThanOrEqualHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\InHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\LessThanHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\LessThanOrEqualHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\LikeHandler;
use Yiisoft\Data\Reader\Iterable\FilterHandler\NotHandler;
use Yiisoft\Data\Reader\Sort;

use function array_merge;
use function array_shift;
use function count;
use function is_string;
use function iterator_to_array;
use function sprintf;
use function uasort;

/**
 * Iterable data reader takes an iterable data as a source and can:
 *
 * - Limit items read
 * - Skip N items from the beginning
 * - Sort items
 * - Form a filter criteria with {@see FilterInterface}
 * - Post-filter items with {@see IterableFilterHandlerInterface}
 *
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @implements DataReaderInterface<TKey, TValue>
 */
final class IterableDataReader implements DataReaderInterface
{
    private ?Sort $sort = null;
    private ?FilterInterface $filter = null;
    private int $limit = 0;
    private int $offset = 0;

    /**
     * @psalm-var array<string, IterableFilterHandlerInterface>
     */
    private array $iterableFilterHandlers = [];

    /**
     * @param iterable $data Data to iterate.
     * @psalm-param iterable<TKey, TValue> $data
     */
    public function __construct(private iterable $data)
    {
        $this->iterableFilterHandlers = $this->prepareFilterHandlers([
            new AllHandler(),
            new AnyHandler(),
            new BetweenHandler(),
            new EqualsHandler(),
            new EqualsEmptyHandler(),
            new EqualsNullHandler(),
            new GreaterThanHandler(),
            new GreaterThanOrEqualHandler(),
            new InHandler(),
            new LessThanHandler(),
            new LessThanOrEqualHandler(),
            new LikeHandler(),
            new NotHandler(),
        ]);
    }

    /**
     * @psalm-return $this
     */
    public function withFilterHandlers(FilterHandlerInterface ...$filterHandlers): static
    {
        $new = clone $this;
        $new->iterableFilterHandlers = array_merge(
            $this->iterableFilterHandlers,
            $this->prepareFilterHandlers($filterHandlers)
        );
        return $new;
    }

    /**
     * @psalm-return $this
     */
    public function withFilter(?FilterInterface $filter): static
    {
        $new = clone $this;
        $new->filter = $filter;
        return $new;
    }

    /**
     * @psalm-return $this
     */
    public function withLimit(int $limit): static
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('The limit must not be less than 0.');
        }

        $new = clone $this;
        $new->limit = $limit;
        return $new;
    }

    /**
     * @psalm-return $this
     */
    public function withOffset(int $offset): static
    {
        $new = clone $this;
        $new->offset = $offset;
        return $new;
    }

    /**
     * @psalm-return $this
     */
    public function withSort(?Sort $sort): static
    {
        $new = clone $this;
        $new->sort = $sort;
        return $new;
    }

    /**
     * @psalm-return Generator<array-key, TValue, mixed, void>
     */
    public function getIterator(): Generator
    {
        yield from $this->read();
    }

    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    public function count(): int
    {
        return count($this->read());
    }

    /**
     * @psalm-return array<TKey, TValue>
     */
    public function read(): array
    {
        $data = [];
        $skipped = 0;
        $filter = $this->filter?->toCriteriaArray();
        $sortedData = $this->sort === null ? $this->data : $this->sortItems($this->data, $this->sort);

        foreach ($sortedData as $key => $item) {
            // Do not return more than limit items.
            if ($this->limit > 0 && count($data) === $this->limit) {
                /** @infection-ignore-all Here continue === break */
                break;
            }

            // Skip offset items.
            if ($skipped < $this->offset) {
                ++$skipped;
                continue;
            }

            // Filter items.
            if ($filter === null || $this->matchFilter($item, $filter)) {
                $data[$key] = $item;
            }
        }

        return $data;
    }

    public function readOne(): array|object|null
    {
        /** @infection-ignore-all Any value more one in `withLimit()` will be ignored because returned `current()` */
        return $this
            ->withLimit(1)
            ->getIterator()
            ->current();
    }

    /**
     * Return whether an item matches iterable filter.
     *
     * @param array|object $item Item to check.
     * @param array $filter Filter.
     *
     * @return bool Whether an item matches iterable filter.
     */
    private function matchFilter(array|object $item, array $filter): bool
    {
        $operation = array_shift($filter);
        $arguments = $filter;

        if (!is_string($operation)) {
            throw new RuntimeException(
                sprintf(
                    'The operator should be string. The %s is received.',
                    get_debug_type($operation),
                )
            );
        }

        if ($operation === '') {
            throw new RuntimeException('The operator string cannot be empty.');
        }

        $processor = $this->iterableFilterHandlers[$operation] ?? null;

        if ($processor === null) {
            throw new RuntimeException(sprintf('Operation "%s" is not supported.', $operation));
        }

        return $processor->match($item, $arguments, $this->iterableFilterHandlers);
    }

    /**
     * Sorts data items according to the given sort definition.
     *
     * @param iterable $items The items to be sorted.
     * @param Sort $sort The sort definition.
     *
     * @return array The sorted items.
     *
     * @psalm-param iterable<TKey, TValue> $items
     * @psalm-return iterable<TKey, TValue>
     */
    private function sortItems(iterable $items, Sort $sort): iterable
    {
        $criteria = $sort->getCriteria();

        if ($criteria !== []) {
            $items = $this->iterableToArray($items);
            /** @infection-ignore-all */
            uasort(
                $items,
                static function (array|object $itemA, array|object $itemB) use ($criteria) {
                    foreach ($criteria as $key => $order) {
                        /** @psalm-var mixed $valueA */
                        $valueA = ArrayHelper::getValue($itemA, $key);
                        /** @psalm-var mixed $valueB */
                        $valueB = ArrayHelper::getValue($itemB, $key);

                        if ($valueB === $valueA) {
                            continue;
                        }

                        return ($valueA > $valueB xor $order === SORT_DESC) ? 1 : -1;
                    }

                    return 0;
                }
            );
        }

        return $items;
    }

    /**
     * @param FilterHandlerInterface[] $filterHandlers
     *
     * @return IterableFilterHandlerInterface[]
     * @psalm-return array<string, IterableFilterHandlerInterface>
     */
    private function prepareFilterHandlers(array $filterHandlers): array
    {
        $result = [];

        foreach ($filterHandlers as $filterHandler) {
            if (!$filterHandler instanceof IterableFilterHandlerInterface) {
                throw new DataReaderException(
                    sprintf(
                        '%s::withFilterHandlers() accepts instances of %s only.',
                        self::class,
                        IterableFilterHandlerInterface::class
                    )
                );
            }
            $result[$filterHandler->getOperator()] = $filterHandler;
        }

        return $result;
    }

    /**
     * Convert iterable to array.
     *
     * @param iterable $iterable Iterable to convert.
     *
     * @psalm-param iterable<TKey, TValue> $iterable
     *
     * @return array Resulting array.
     * @psalm-return array<TKey, TValue>
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof Traversable ? iterator_to_array($iterable, true) : $iterable;
    }
}
