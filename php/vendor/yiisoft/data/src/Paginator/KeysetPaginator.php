<?php

declare(strict_types=1);

namespace Yiisoft\Data\Paginator;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\Filter\Compare;
use Yiisoft\Data\Reader\Filter\GreaterThan;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\LessThan;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;
use Yiisoft\Data\Reader\FilterableDataInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface;

use function array_reverse;
use function count;
use function key;
use function reset;
use function sprintf;

/**
 * Keyset paginator.
 *
 * Advantages:
 *
 * - Performance does not depend on page number
 * - Consistent results regardless of insertions and deletions
 *
 * Disadvantages:
 *
 * - Total number of pages is not available
 * - Can not get to specific page, only "previous" and "next"
 * - Data cannot be unordered
 *
 * @link https://use-the-index-luke.com/no-offset
 *
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @implements PaginatorInterface<TKey, TValue>
 */
final class KeysetPaginator implements PaginatorInterface
{
    /**
     * Data reader being paginated.
     *
     * @psalm-var ReadableDataInterface<TKey, TValue>&FilterableDataInterface&SortableDataInterface
     */
    private ReadableDataInterface $dataReader;

    /**
     * @var int Maximum number of items per page.
     */
    private int $pageSize = self::DEFAULT_PAGE_SIZE;
    private ?string $firstValue = null;
    private ?string $lastValue = null;
    private ?string $currentFirstValue = null;
    private ?string $currentLastValue = null;

    /**
     * @var bool Whether there is previous page.
     */
    private bool $hasPreviousPage = false;

    /**
     * @var bool Whether there is next page.
     */
    private bool $hasNextPage = false;

    /**
     * Reader cache against repeated scans.
     * See more {@see __clone()} and {@see initialize()}.
     *
     * @psalm-var null|array<TKey, TValue>
     */
    private ?array $readCache = null;

    /**
     * @param ReadableDataInterface $dataReader Data reader being paginated.
     * @psalm-param ReadableDataInterface<TKey, TValue>&FilterableDataInterface&SortableDataInterface $dataReader
     * @psalm-suppress DocblockTypeContradiction Needed to allow validating `$dataReader`
     */
    public function __construct(ReadableDataInterface $dataReader)
    {
        if (!$dataReader instanceof FilterableDataInterface) {
            throw new InvalidArgumentException(sprintf(
                'Data reader should implement "%s" to be used with keyset paginator.',
                FilterableDataInterface::class,
            ));
        }

        if (!$dataReader instanceof SortableDataInterface) {
            throw new InvalidArgumentException(sprintf(
                'Data reader should implement "%s" to be used with keyset paginator.',
                SortableDataInterface::class,
            ));
        }

        $sort = $dataReader->getSort();

        if ($sort === null) {
            throw new RuntimeException('Data sorting should be configured to work with keyset pagination.');
        }

        if (empty($sort->getOrder())) {
            throw new RuntimeException('Data should be always sorted to work with keyset pagination.');
        }

        $this->dataReader = $dataReader;
    }

    public function __clone()
    {
        $this->readCache = null;
        $this->hasPreviousPage = false;
        $this->hasNextPage = false;
        $this->currentFirstValue = null;
        $this->currentLastValue = null;
    }

    public function withNextPageToken(?string $token): static
    {
        $new = clone $this;
        $new->firstValue = null;
        $new->lastValue = $token;
        return $new;
    }

    public function withPreviousPageToken(?string $token): static
    {
        $new = clone $this;
        $new->firstValue = $token;
        $new->lastValue = null;
        return $new;
    }

    public function withPageSize(int $pageSize): static
    {
        if ($pageSize < 1) {
            throw new InvalidArgumentException('Page size should be at least 1.');
        }

        $new = clone $this;
        $new->pageSize = $pageSize;
        return $new;
    }

    /**
     * Reads items of the page.
     *
     * This method uses the read cache to prevent duplicate reads from the data source. See more {@see resetInternal()}.
     */
    public function read(): iterable
    {
        if ($this->readCache !== null) {
            return $this->readCache;
        }

        /** @var Sort $sort */
        $sort = $this->dataReader->getSort();
        /** @infection-ignore-all Any value more one in line below will be ignored into `readData()` method */
        $dataReader = $this->dataReader->withLimit($this->pageSize + 1);

        if ($this->isGoingToPreviousPage()) {
            $sort = $this->reverseSort($sort);
            $dataReader = $dataReader->withSort($sort);
        }

        if ($this->isGoingSomewhere()) {
            $dataReader = $dataReader->withFilter($this->getFilter($sort));
            $this->hasPreviousPage = $this->previousPageExist($dataReader, $sort);
        }

        $data = $this->readData($dataReader, $sort);

        if ($this->isGoingToPreviousPage()) {
            $data = $this->reverseData($data);
        }

        return $this->readCache = $data;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getCurrentPageSize(): int
    {
        $this->initialize();
        return count($this->readCache);
    }

    public function getPreviousPageToken(): ?string
    {
        return $this->isOnFirstPage() ? null : $this->currentFirstValue;
    }

    public function getNextPageToken(): ?string
    {
        return $this->isOnLastPage() ? null : $this->currentLastValue;
    }

    public function getSort(): ?Sort
    {
        /** @psalm-var SortableDataInterface $this->dataReader */
        return $this->dataReader->getSort();
    }

    public function isOnFirstPage(): bool
    {
        if ($this->lastValue === null && $this->firstValue === null) {
            return true;
        }

        $this->initialize();
        return !$this->hasPreviousPage;
    }

    public function isOnLastPage(): bool
    {
        $this->initialize();
        return !$this->hasNextPage;
    }

    public function isRequired(): bool
    {
        return !$this->isOnFirstPage() || !$this->isOnLastPage();
    }

    /**
     * @psalm-assert array<TKey, TValue> $this->readCache
     */
    private function initialize(): void
    {
        if ($this->readCache !== null) {
            return;
        }

        $cache = [];

        foreach ($this->read() as $key => $value) {
            $cache[$key] = $value;
        }

        $this->readCache = $cache;
    }

    /**
     * @psalm-param ReadableDataInterface<TKey, TValue> $dataReader
     * @psalm-return array<TKey, TValue>
     */
    private function readData(ReadableDataInterface $dataReader, Sort $sort): array
    {
        $data = [];
        /** @var string $field */
        [$field] = $this->getFieldAndSortingFromSort($sort);

        foreach ($dataReader->read() as $key => $item) {
            if ($this->currentFirstValue === null) {
                $this->currentFirstValue = (string) ArrayHelper::getValue($item, $field);
            }

            if (count($data) === $this->pageSize) {
                $this->hasNextPage = true;
            } else {
                $this->currentLastValue = (string) ArrayHelper::getValue($item, $field);
                $data[$key] = $item;
            }
        }

        return $data;
    }

    /**
     * @psalm-param array<TKey, TValue> $data
     * @psalm-return array<TKey, TValue>
     */
    private function reverseData(array $data): array
    {
        [$this->currentFirstValue, $this->currentLastValue] = [$this->currentLastValue, $this->currentFirstValue];
        [$this->hasPreviousPage, $this->hasNextPage] = [$this->hasNextPage, $this->hasPreviousPage];
        return array_reverse($data, true);
    }

    /**
     * @psalm-param ReadableDataInterface<TKey, TValue>&FilterableDataInterface&SortableDataInterface $dataReader
     */
    private function previousPageExist(ReadableDataInterface $dataReader, Sort $sort): bool
    {
        $reverseFilter = $this->getReverseFilter($sort);

        return !empty($dataReader->withFilter($reverseFilter)->readOne());
    }

    private function getFilter(Sort $sort): Compare
    {
        $value = $this->getValue();
        /** @var string $field */
        [$field, $sorting] = $this->getFieldAndSortingFromSort($sort);
        return $sorting === 'asc' ? new GreaterThan($field, $value) : new LessThan($field, $value);
    }

    private function getReverseFilter(Sort $sort): Compare
    {
        $value = $this->getValue();
        /** @var string $field */
        [$field, $sorting] = $this->getFieldAndSortingFromSort($sort);
        return $sorting === 'asc' ? new LessThanOrEqual($field, $value) : new GreaterThanOrEqual($field, $value);
    }

    /**
     * @psalm-suppress NullableReturnStatement, InvalidNullableReturnType The code calling this method
     * must ensure that at least one of the properties `$firstValue` or `$lastValue` is not `null`.
     */
    private function getValue(): string
    {
        return $this->isGoingToPreviousPage() ? $this->firstValue : $this->lastValue;
    }

    private function reverseSort(Sort $sort): Sort
    {
        $order = $sort->getOrder();

        foreach ($order as &$sorting) {
            $sorting = $sorting === 'asc' ? 'desc' : 'asc';
        }

        return $sort->withOrder($order);
    }

    private function getFieldAndSortingFromSort(Sort $sort): array
    {
        $order = $sort->getOrder();

        return [
            (string) key($order),
            reset($order),
        ];
    }

    private function isGoingToPreviousPage(): bool
    {
        return $this->firstValue !== null && $this->lastValue === null;
    }

    private function isGoingSomewhere(): bool
    {
        return $this->firstValue !== null || $this->lastValue !== null;
    }
}
