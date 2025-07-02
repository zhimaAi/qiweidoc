<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonBatchQueryResultTest;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class BatchQueryResultTest extends CommonBatchQueryResultTest
{
    use TestTrait;
}
