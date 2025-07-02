<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Dsn;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnTest extends TestCase
{
    public function testAsString(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=yiitest;port=5432',
            (new Dsn('pgsql', 'localhost', 'yiitest'))->asString(),
        );
    }

    public function testAsStringWithDatabaseName(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=postgres;port=5432',
            (new Dsn('pgsql', 'localhost'))->asString(),
        );
    }

    public function testAsStringWithDatabaseNameWithEmptyString(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=postgres;port=5432',
            (new Dsn('pgsql', 'localhost', ''))->asString(),
        );
    }

    public function testAsStringWithDatabaseNameWithNull(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=postgres;port=5432',
            (new Dsn('pgsql', 'localhost', null))->asString(),
        );
    }

    public function testAsStringWithOptions(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=yiitest;port=5433;charset=utf8',
            (new Dsn('pgsql', 'localhost', 'yiitest', '5433', ['charset' => 'utf8']))->asString(),
        );
    }

    public function testAsStringWithPort(): void
    {
        $this->assertSame(
            'pgsql:host=localhost;dbname=yiitest;port=5433',
            (new Dsn('pgsql', 'localhost', 'yiitest', '5433'))->asString(),
        );
    }
}
