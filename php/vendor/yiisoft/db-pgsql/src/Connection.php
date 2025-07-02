<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\Driver\Pdo\PdoCommandInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Implements a connection to a database via PDO (PHP Data Objects) for PostgreSQL Server.
 *
 * @link https://www.php.net/manual/en/ref.pdo-pgsql.php
 */
final class Connection extends AbstractPdoConnection
{
    public function createCommand(string $sql = null, array $params = []): PdoCommandInterface
    {
        $command = new Command($this);

        if ($sql !== null) {
            $command->setSql($sql);
        }

        if ($this->logger !== null) {
            $command->setLogger($this->logger);
        }

        if ($this->profiler !== null) {
            $command->setProfiler($this->profiler);
        }

        return $command->bindValues($params);
    }

    public function createTransaction(): TransactionInterface
    {
        return new Transaction($this);
    }

    public function getLastInsertID(string $sequenceName = null): string
    {
        if ($sequenceName === null) {
            throw new InvalidArgumentException('PostgreSQL not support lastInsertId without sequence name.');
        }

        return parent::getLastInsertID($sequenceName);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder(
                $this->getQuoter(),
                $this->getSchema(),
            );
        }

        return $this->queryBuilder;
    }

    public function getQuoter(): QuoterInterface
    {
        if ($this->quoter === null) {
            $this->quoter = new Quoter('"', '"', $this->getTablePrefix());
        }

        return $this->quoter;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this, $this->schemaCache);
        }

        return $this->schema;
    }
}
