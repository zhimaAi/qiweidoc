<?php

declare(strict_types=1);

namespace Yiisoft\Data\Paginator;

use Generator;
use InvalidArgumentException;
use Yiisoft\Data\Reader\CountableDataInterface;
use Yiisoft\Data\Reader\OffsetableDataInterface;
use Yiisoft\Data\Reader\ReadableDataInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface;

use function ceil;
use function max;
use function sprintf;

/**
 * Offset paginator.
 *
 * Advantages:
 *
 * - Total number of pages is available
 * - Can get to specific page
 * - Data can be unordered
 *
 * Disadvantages:
 *
 * - Performance degrades with page number increase
 * - Insertions or deletions in the middle of the data are making results inconsistent
 *
 * @template TKey as array-key
 * @template TValue as array|object
 *
 * @implements PaginatorInterface<TKey, TValue>
 */
final class OffsetPaginator implements PaginatorInterface
{
    /**
     * @var int Current page number.
     */
    private int $currentPage = 1;

    /**
     * @var int Maximum number of items per page.
     */
    private int $pageSize = self::DEFAULT_PAGE_SIZE;

    /**
     * Data reader being paginated.
     *
     * @psalm-var ReadableDataInterface<TKey, TValue>&OffsetableDataInterface&CountableDataInterface
     */
    private ReadableDataInterface $dataReader;

    /**
     * @param ReadableDataInterface $dataReader Data reader being paginated.
     * @psalm-param ReadableDataInterface<TKey, TValue>&OffsetableDataInterface&CountableDataInterface $dataReader
     * @psalm-suppress DocblockTypeContradiction Needed to allow validating `$dataReader`
     */
    public function __construct(ReadableDataInterface $dataReader)
    {
        if (!$dataReader instanceof OffsetableDataInterface) {
            throw new InvalidArgumentException(sprintf(
                'Data reader should implement "%s" in order to be used with offset paginator.',
                OffsetableDataInterface::class,
            ));
        }

        if (!$dataReader instanceof CountableDataInterface) {
            throw new InvalidArgumentException(sprintf(
                'Data reader should implement "%s" in order to be used with offset paginator.',
                CountableDataInterface::class,
            ));
        }

        $this->dataReader = $dataReader;
    }

    public function withNextPageToken(?string $token): static
    {
        return $this->withCurrentPage((int) $token);
    }

    public function withPreviousPageToken(?string $token): static
    {
        return $this->withCurrentPage((int) $token);
    }

    public function withPageSize(int $pageSize): static
    {
        if ($pageSize < 1) {
            throw new PaginatorException('Page size should be at least 1.');
        }

        $new = clone $this;
        $new->pageSize = $pageSize;
        return $new;
    }

    /**
     * Get a new instance with the given current page number set.
     *
     * @param int $page Page number.
     *
     * @throws PaginatorException If current page is set incorrectly.
     *
     * @return self New instance.
     */
    public function withCurrentPage(int $page): self
    {
        if ($page < 1) {
            throw new PaginatorException('Current page should be at least 1.');
        }

        $new = clone $this;
        $new->currentPage = $page;
        return $new;
    }

    public function getNextPageToken(): ?string
    {
        return $this->isOnLastPage() ? null : (string) ($this->currentPage + 1);
    }

    public function getPreviousPageToken(): ?string
    {
        return $this->isOnFirstPage() ? null : (string) ($this->currentPage - 1);
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Get current page number.
     *
     * @return int Current page number.
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getCurrentPageSize(): int
    {
        $pages = $this->getInternalTotalPages();

        if ($pages === 1) {
            return $this->getTotalItems();
        }

        if ($this->currentPage < $pages) {
            return $this->pageSize;
        }

        if ($this->currentPage === $pages) {
            return $this->getTotalItems() - $this->getOffset();
        }

        throw new PaginatorException('Page not found.');
    }

    /**
     * Get offset for the current page i.e. the number of items to skip before the current page is reached.
     *
     * @return int Offset.
     */
    public function getOffset(): int
    {
        return $this->pageSize * ($this->currentPage - 1);
    }

    /**
     * Get total number of items in the whole data reader being paginated.
     *
     * @return int Total items number.
     */
    public function getTotalItems(): int
    {
        return $this->dataReader->count();
    }

    /**
     * Get total number of pages in a data reader being paginated.
     *
     * @return int Total pages number.
     */
    public function getTotalPages(): int
    {
        return (int) ceil($this->getTotalItems() / $this->pageSize);
    }

    public function getSort(): ?Sort
    {
        return $this->dataReader instanceof SortableDataInterface ? $this->dataReader->getSort() : null;
    }

    /**
     * @psalm-return Generator<TKey, TValue, mixed, null>
     */
    public function read(): iterable
    {
        if ($this->currentPage > $this->getInternalTotalPages()) {
            throw new PaginatorException('Page not found.');
        }

        yield from $this->dataReader
            ->withLimit($this->pageSize)
            ->withOffset($this->getOffset())
            ->read();
    }

    public function isOnFirstPage(): bool
    {
        return $this->currentPage === 1;
    }

    public function isOnLastPage(): bool
    {
        if ($this->currentPage > $this->getInternalTotalPages()) {
            throw new PaginatorException('Page not found.');
        }

        return $this->currentPage === $this->getInternalTotalPages();
    }

    public function isRequired(): bool
    {
        return $this->getTotalPages() > 1;
    }

    private function getInternalTotalPages(): int
    {
        return max(1, $this->getTotalPages());
    }
}
