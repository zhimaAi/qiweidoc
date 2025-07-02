<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function array_map;
use function implode;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for PostgreSQL Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        $sql = $this->insert($table, $columns, $params);
        $returnColumns = $this->schema->getTableSchema($table)?->getPrimaryKey();

        if (!empty($returnColumns)) {
            $returnColumns = array_map(
                [$this->quoter, 'quoteColumnName'],
                $returnColumns,
            );

            $sql .= ' RETURNING ' . implode(', ', $returnColumns);
        }

        return $sql;
    }

    public function resetSequence(string $table, int|string $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        $sequence = $tableSchema->getSequenceName();

        if ($sequence === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$table'.");
        }

        /** @link https://www.postgresql.org/docs/8.1/static/functions-sequence.html */
        $sequence = $this->quoter->quoteTableName($sequence);

        if ($value === null) {
            $table = $this->quoter->quoteTableName($table);
            $key = $tableSchema->getPrimaryKey()[0];
            $key = $this->quoter->quoteColumnName($key);
            $value = "(SELECT COALESCE(MAX($key),0) FROM $table)+1";
        }

        return "SELECT SETVAL('$sequence',$value,false)";
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
    ): string {
        $insertSql = $this->insert($table, $insertColumns, $params);

        [$uniqueNames, , $updateNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $insertSql;
        }

        if ($updateColumns === false || $updateNames === []) {
            /** there are no columns to update */
            return "$insertSql ON CONFLICT DO NOTHING";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $updateColumns[$name] = new Expression(
                    'EXCLUDED.' . $this->quoter->quoteColumnName($name)
                );
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return $insertSql
            . ' ON CONFLICT (' . implode(', ', $uniqueNames) . ') DO UPDATE SET ' . implode(', ', $updates);
    }
}
