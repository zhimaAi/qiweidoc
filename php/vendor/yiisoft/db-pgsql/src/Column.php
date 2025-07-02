<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Schema\Builder\AbstractColumn;

/**
 * It's a utility that provides a convenient way to create column schema for use with {@see Schema}
 * for PostgreSQL.
 *
 * It provides methods for specifying the properties of a column, such as its type, size, default value, and whether it
 * is nullable or not. It also provides a method for creating a column schema based on the specified properties.
 *
 * For example, the following code creates a column schema for an integer column:
 *
 * ```php
 * $column = (new Column(SchemaInterface::TYPE_INTEGER))->notNull()->defaultValue(0);
 * ```
 *
 * Provides a fluent interface, which means that the methods can be chained together to create a column schema with
 * many properties in a single line of code.
 */
final class Column extends AbstractColumn
{
}
