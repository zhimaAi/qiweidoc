<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use PDO;
use Yiisoft\Db\Driver\Pdo\AbstractPdoDriver;

/**
 * Implements the PostgreSQL Server driver based on the PDO (PHP Data Objects) extension.
 *
 * @link https://www.php.net/manual/en/ref.pdo-pgsql.php
 */
final class Driver extends AbstractPdoDriver
{
    public function createConnection(): PDO
    {
        $this->attributes += [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $pdo = parent::createConnection();

        if ($this->charset !== null) {
            $pdo->exec('SET NAMES ' . $pdo->quote($this->charset));
        }

        return $pdo;
    }

    public function getDriverName(): string
    {
        return 'pgsql';
    }
}
