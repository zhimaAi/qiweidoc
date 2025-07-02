<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * Implements the PostgreSQL Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @var array Mapping from abstract column types (keys) to physical column types (values).
     *
     * @psalm-var string[]
     */
    protected array $typeMap = [
        SchemaInterface::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_UPK => 'serial NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_BIGPK => 'bigserial NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_UBIGPK => 'bigserial NOT NULL PRIMARY KEY',
        SchemaInterface::TYPE_CHAR => 'char(1)',
        SchemaInterface::TYPE_STRING => 'varchar(255)',
        SchemaInterface::TYPE_TEXT => 'text',
        SchemaInterface::TYPE_TINYINT => 'smallint',
        SchemaInterface::TYPE_SMALLINT => 'smallint',
        SchemaInterface::TYPE_INTEGER => 'integer',
        SchemaInterface::TYPE_BIGINT => 'bigint',
        SchemaInterface::TYPE_FLOAT => 'double precision',
        SchemaInterface::TYPE_DOUBLE => 'double precision',
        SchemaInterface::TYPE_DECIMAL => 'numeric(10,0)',
        SchemaInterface::TYPE_DATETIME => 'timestamp(0)',
        SchemaInterface::TYPE_TIMESTAMP => 'timestamp(0)',
        SchemaInterface::TYPE_TIME => 'time(0)',
        SchemaInterface::TYPE_DATE => 'date',
        SchemaInterface::TYPE_BINARY => 'bytea',
        SchemaInterface::TYPE_BOOLEAN => 'boolean',
        SchemaInterface::TYPE_MONEY => 'numeric(19,4)',
        SchemaInterface::TYPE_JSON => 'jsonb',
        SchemaInterface::TYPE_UUID => 'uuid',
        SchemaInterface::TYPE_UUID_PK => 'uuid PRIMARY KEY',
    ];

    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter);
        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
    }
}
