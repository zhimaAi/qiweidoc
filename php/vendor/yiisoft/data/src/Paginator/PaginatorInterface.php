<?php

declare(strict_types=1);

namespace Yiisoft\Data\Paginator;

use Yiisoft\Data\Reader\Sort;

/**
 * Paginator interface defines a common pagination methods shared by all pagination types.
 *
 * - Each page has string "tokens" for getting to previous and next page.
 * - Maximum number of items per page could be set.
 *
 * @template TKey as array-key
 * @template TValue as array|object
 */
interface PaginatorInterface
{
    /**
     * Page size that is used in case it is not set explicitly.
     */
    public const DEFAULT_PAGE_SIZE = 10;

    /**
     * Get a new instance with token for the next page set.
     *
     * @param string|null $token Token for the next page. Null if current page is last.
     *
     * @return static New instance.
     */
    public function withNextPageToken(?string $token): static;

    /**
     * Get a new instance with token for the previous page set.
     *
     * @param string|null $token Token for the previous page. Null if current page is first.
     *
     * @return static New instance.
     */
    public function withPreviousPageToken(?string $token): static;

    /**
     * Get a new instance with page size set.
     *
     * @param int $pageSize Maximum number of items per page.
     *
     * @throws PaginatorException If page size is incorrect.
     *
     * @return static New instance.
     */
    public function withPageSize(int $pageSize): static;

    /**
     * Get token for the next page.
     *
     * @return string|null Token for the next page. Null if current page is last.
     */
    public function getNextPageToken(): ?string;

    /**
     * Get token for the previous page.
     *
     * @return string|null Token for the previous page. Null if current page is first.
     */
    public function getPreviousPageToken(): ?string;

    /**
     * Get maximum number of items per page.
     *
     * Note that there could be less current page items.
     *
     * @see getCurrentPageSize()
     *
     * @return int Page size.
     */
    public function getPageSize(): int;

    /**
     * Get number of items at the current page.
     *
     * Note that it is actual number of items, not the limit.
     *
     * @see getPageSize()
     *
     * @throws PaginatorException If page specified is not found.
     *
     * @return int Current page size.
     */
    public function getCurrentPageSize(): int;

    /**
     * Get current sort object.
     *
     * @return Sort|null Current sort object or null if no sorting is used.
     */
    public function getSort(): ?Sort;

    /**
     * Get iterator that could be used to read currently active page items.
     *
     * @throws PaginatorException If page specified is not found.
     *
     * @return iterable Iterator with items for the current page.
     * @psalm-return iterable<TKey, TValue>
     */
    public function read(): iterable;

    /**
     * Get whether current page is the last one.
     *
     * @throws PaginatorException If page specified is not found.
     *
     * @return bool Whether current page is the last one.
     */
    public function isOnLastPage(): bool;

    /**
     * Get whether current page is the first one.
     *
     * @return bool Whether current page is the first one.
     */
    public function isOnFirstPage(): bool;

    /**
     * Check that there is more than a single page so pagination is needed.
     *
     * @return bool Whether pagination is required.
     */
    public function isRequired(): bool;
}
