<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Tests\Support\DbHelper;

trait TestTrait
{
    private string $dsn = '';
    private string $fixture = 'pgsql.sql';

    /**
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function getConnection(bool $fixture = false): PdoConnectionInterface
    {
        $pdoDriver = new Driver($this->getDsn(), 'root', 'root');
        $pdoDriver->charset('utf8');
        $db = new Connection($pdoDriver, DbHelper::getSchemaCache());

        if ($fixture) {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$this->fixture");
        }

        return $db;
    }

    protected static function getDb(): PdoConnectionInterface
    {
        $dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();

        return new Connection(new Driver($dsn, 'root', 'root'), DbHelper::getSchemaCache());
    }

    protected function getDsn(): string
    {
        if ($this->dsn === '') {
            $this->dsn = (new Dsn('pgsql', '127.0.0.1', 'yiitest', '5432'))->asString();
        }

        return $this->dsn;
    }

    protected function getDriverName(): string
    {
        return 'pgsql';
    }

    protected function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }

    protected function setFixture(string $fixture): void
    {
        $this->fixture = $fixture;
    }
}
