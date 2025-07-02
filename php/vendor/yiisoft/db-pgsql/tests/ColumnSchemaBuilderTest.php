<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonColumnSchemaBuilderTest;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaBuilderTest extends CommonColumnSchemaBuilderTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\ColumnSchemaBuilderProvider::createColumnTypes
     */
    public function testCreateColumnTypes(string $expected, string $type, ?int $length, array $calls): void
    {
        parent::testCreateColumnTypes($expected, $type, $length, $calls);
    }
}
