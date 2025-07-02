<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Support;

use Yiisoft\Db\Pgsql\ColumnSchema;

class ColumnSchemaBuilder
{
    public static function numeric(string $name, int|null $precision, int|null $scale, mixed $defaultValue = null): ColumnSchema
    {
        $column = new ColumnSchema($name);
        $column->type('decimal');
        $column->dbType('numeric');
        $column->phpType('double');
        $column->precision($precision);
        $column->scale($scale);
        $column->defaultValue($defaultValue);

        return $column;
    }

    public static function char(string $name, int|null $size, mixed $defaultValue = null): ColumnSchema
    {
        $column = new ColumnSchema($name);
        $column->type('char');
        $column->dbType('bpchar');
        $column->phpType('string');
        $column->size($size);
        $column->defaultValue($defaultValue);

        return $column;
    }
}
