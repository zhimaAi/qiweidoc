<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * AbstractMigrationBuilder contains shortcut methods to create instances of {@see ColumnInterface}.
 *
 * These can be used in database migrations to define database schema types using a PHP interface. This is useful to
 * define a schema in a DBMS independent way so that the application may run on different DBMS the same way.
 *
 * For example, you may use the following code inside your migration files:
 *
 * ```php
 * $this->createTable(
 *     'example_table',
 *     [
 *         'id' => $this->primaryKey(),
 *         'name' => $this->string(64)->notNull(),
 *         'type' => $this->integer()->notNull()->defaultValue(10),
 *         'description' => $this->text(),
 *         'rule_name' => $this->string(64),
 *         'data' => $this->text(),
 *         'created_at' => $this->datetime()->notNull(),
 *         'updated_at' => $this->datetime(),
 *     ],
 * );
 * ```
 */
abstract class AbstractMigrationBuilder
{
    public function __construct(private SchemaInterface $schema)
    {
    }

    /**
     * Creates a bigint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function bigInteger(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_BIGINT, $length);
    }

    /**
     * Creates a big primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function bigPrimaryKey(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_BIGPK, $length);
    }

    /**
     * Creates a UUID primary key column.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function uuidPrimaryKey(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_UUID_PK);
    }

    /**
     * Creates a UUID primary key column with a sequence.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function uuidPrimaryKeySequenced(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_UUID_PK_SEQ);
    }

    /**
     * Creates a UUID column.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function uuid(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_UUID);
    }

    /**
     * Creates a binary column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function binary(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_BINARY, $length);
    }

    /**
     * Creates a boolean column.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function boolean(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_BOOLEAN);
    }

    /**
     * Creates a char column.
     *
     * @param int|null $length the column size definition, i.e., the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function char(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_CHAR, $length);
    }

    /**
     * Creates a date column.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function date(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_DATE);
    }

    /**
     * Creates a datetime column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DATETIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function dateTime(int $precision = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_DATETIME, $precision);
    }

    /**
     * Creates a decimal column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function decimal(int $precision = null, int $scale = null): ColumnInterface
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->schema->createColumn(SchemaInterface::TYPE_DECIMAL, $length);
    }

    /**
     * Creates a double column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DOUBLE(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function double(int $precision = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_DOUBLE, $precision);
    }

    /**
     * Creates a float column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * FLOAT(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function float(int $precision = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_FLOAT, $precision);
    }

    /**
     * Creates an integer column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function integer(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_INTEGER, $length);
    }

    /**
     * Creates a JSON column.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function json(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_JSON);
    }

    /**
     * Creates a money column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits. First
     * parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g., DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function money(int $precision = null, int $scale = null): ColumnInterface
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->schema->createColumn(SchemaInterface::TYPE_MONEY, $length);
    }

    /**
     * Creates a primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function primaryKey(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_PK, $length);
    }

    /**
     * Creates a smallint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function smallInteger(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_SMALLINT, $length);
    }

    /**
     * Creates a string column.
     *
     * @param int|null $length The column size definition, i.e., the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function string(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_STRING, $length);
    }

    /**
     * Creates a text column.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function text(): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_TEXT);
    }

    /**
     * Creates a time column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function time(int $precision = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_TIME, $precision);
    }

    /**
     * Creates a timestamp column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIMESTAMP(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function timestamp(int $precision = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_TIMESTAMP, $precision);
    }

    /**
     * Creates a tinyint column. If tinyint is not supported by the DBMS, smallint will be used.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnInterface The column instance which can be further customized.
     */
    public function tinyInteger(int $length = null): ColumnInterface
    {
        return $this->schema->createColumn(SchemaInterface::TYPE_TINYINT, $length);
    }
}
