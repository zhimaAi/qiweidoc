<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQuoterTest;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QuoterTest extends AbstractQuoterTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\QuoterProvider::tableNameParts
     */
    public function testGetTableNameParts(string $tableName, string ...$expected): void
    {
        parent::testGetTableNameParts($tableName, ...$expected);
    }
}
