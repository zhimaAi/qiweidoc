<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

final class SchemaProvider extends \Yiisoft\Db\Tests\Provider\SchemaProvider
{
    public static function columns(): array
    {
        return [
            [
                [
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 16,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'int2',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 16,
                        'scale' => 0,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'bpchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 'some\'thing',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col4' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => "first line\nsecond line",
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 53,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'float8',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 53,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'bytea',
                        'phpType' => 'resource',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 'a binary value',
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'numeric',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => 33.22,
                    ],
                    'time' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'bool_col' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'boolean',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'boolean',
                        'dbType' => 'bool',
                        'phpType' => 'boolean',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => true,
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => new Expression('now()'),
                    ],
                    'bit_col' => [
                        'type' => 'bit',
                        'dbType' => 'bit',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 8,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 0b1000_0010, // 130
                    ],
                    'varbit_col' => [
                        'type' => 'bit',
                        'dbType' => 'varbit',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 0b100, // 4
                    ],
                    'bigint_col' => [
                        'type' => 'bigint',
                        'dbType' => 'int8',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 64,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'intarray_col' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                    'numericarray_col' => [
                        'type' => 'decimal',
                        'dbType' => 'numeric',
                        'phpType' => 'double',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                    'varchararray_col' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                    'textarray2_col' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 2,
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => ['a' => 1],
                        'dimension' => 0,
                    ],
                    'jsonb_col' => [
                        'type' => 'json',
                        'dbType' => 'jsonb',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 0,
                    ],
                    'jsonarray_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                        'dimension' => 1,
                    ],
                ],
                'tableName' => 'type',
            ],
            [
                [
                    'id' => [
                        'type' => 'integer',
                        'dbType' => 'int4',
                        'phpType' => 'integer',
                        'primaryKey' => true,
                        'allowNull' => false,
                        'autoIncrement' => true,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => 32,
                        'scale' => 0,
                        'defaultValue' => null,
                    ],
                    'type' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 255,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'animal',
            ],
            [
                [
                    'uuid' => [
                        'type' => 'string',
                        'dbType' => 'uuid',
                        'phpType' => 'string',
                        'primaryKey' => true,
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'col' => [
                        'type' => 'string',
                        'dbType' => 'varchar',
                        'phpType' => 'string',
                        'primaryKey' => false,
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 16,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                ],
                'table_uuid',
            ],
        ];
    }

    public static function columnsTypeChar(): array
    {
        $columnsTypeChar = parent::columnsTypeChar();

        $columnsTypeChar[0][3] = 'bpchar';
        $columnsTypeChar[1][3] = 'varchar';

        return $columnsTypeChar;
    }

    public static function constraints(): array
    {
        $constraints = parent::constraints();

        $constraints['1: check'][2][0]->expression('CHECK ((("C_check")::text <> \'\'::text))');
        $constraints['3: foreign key'][2][0]->foreignSchemaName('public');
        $constraints['3: index'][2] = [];

        return $constraints;
    }

    public static function constraintsOfView(): array
    {
        $constraints = self::constraints();

        foreach ($constraints as $key => $constraint) {
            $constraints[$key][0] = $constraint[0] . '_view';
        }

        return $constraints;
    }

    public static function tableSchemaCacheWithTablePrefixes(): array
    {
        $configs = [
            ['prefix' => '', 'name' => 'type'],
            ['prefix' => '', 'name' => '{{%type}}'],
            ['prefix' => 'ty', 'name' => '{{%pe}}'],
        ];

        $data = [];

        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix']
                );
                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }

    public static function tableSchemaWithDbSchemes(): array
    {
        return [
            ['animal', 'animal', 'public'],
            ['public.animal', 'animal', 'public'],
            ['"public"."animal"', 'animal', 'public'],
            ['"other"."animal2"', 'animal2', 'other',],
            ['other."animal2"', 'animal2', 'other',],
            ['other.animal2', 'animal2', 'other',],
            ['catalog.other.animal2', 'animal2', 'other'],
        ];
    }
}
