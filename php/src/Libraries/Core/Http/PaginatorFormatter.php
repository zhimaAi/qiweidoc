<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace App\Libraries\Core\Http;

use Yiisoft\Data\Paginator\OffsetPaginator;

/**
 * @OA\Schema(
 *      schema="Paginator",
 *
 *      @OA\Property(example="10", property="pageSize", format="int"),
 *      @OA\Property(example="1", property="currentPage", format="int"),
 *      @OA\Property(example="3", property="totalPages", format="int"),
 * )
 */
final class PaginatorFormatter
{
    public function format(OffsetPaginator $paginator): array
    {
        return [
            'pageSize' => $paginator->getPageSize(),
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
        ];
    }
}
