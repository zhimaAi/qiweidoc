<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\StructuredExpression;
use Yiisoft\Db\Pgsql\Tests\Support\ColumnSchemaBuilder;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'pgsql';

    public static function buildCondition(): array
    {
        $buildCondition = parent::buildCondition();

        $priceColumns = [
            'value' => ColumnSchemaBuilder::numeric(name: 'value', precision: 10, scale: 2),
            'currency_code' => ColumnSchemaBuilder::char(name: 'currency_code', size: 3),
        ];

        return array_merge(
            $buildCondition,
            [
                /**
                * adding conditions for ILIKE i.e. case insensitive LIKE.
                *
                * {@see https://www.postgresql.org/docs/8.3/static/functions-matching.html#FUNCTIONS-LIKE}
                */
                /* empty values */
                [['ilike', 'name', []], '0=1', []],
                [['not ilike', 'name', []], '', []],
                [['or ilike', 'name', []], '0=1', []],
                [['or not ilike', 'name', []], '', []],

                /* simple ilike */
                [['ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => '%heyho%']],
                [['not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => '%heyho%']],
                [['or ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => '%heyho%']],
                [['or not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => '%heyho%']],

                /* ilike for many values */
                [
                    ['ilike', 'name', ['heyho', 'abc']],
                    '"name" ILIKE :qp0 AND "name" ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['not ilike', 'name', ['heyho', 'abc']],
                    '"name" NOT ILIKE :qp0 AND "name" NOT ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['or ilike', 'name', ['heyho', 'abc']],
                    '"name" ILIKE :qp0 OR "name" ILIKE :qp1', [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['or not ilike', 'name', ['heyho', 'abc']],
                    '"name" NOT ILIKE :qp0 OR "name" NOT ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],

                /* array condition corner cases */
                [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
                'scalar can not be converted to array #1' => [['@>', 'id', new ArrayExpression(1)], '"id" @> ARRAY[]', []],
                [
                    'scalar can not be converted to array #2' => [
                        '@>', 'id', new ArrayExpression(false),
                    ],
                    '"id" @> ARRAY[]',
                    [],
                ],
                [
                    ['&&', 'price', new ArrayExpression([12, 14], 'float')],
                    '"price" && ARRAY[:qp0, :qp1]::float[]',
                    [':qp0' => 12, ':qp1' => 14],
                ],
                [
                    ['@>', 'id', new ArrayExpression([2, 3])],
                    '"id" @> ARRAY[:qp0, :qp1]',
                    [':qp0' => 2, ':qp1' => 3],
                ],
                'array of arrays' => [
                    ['@>', 'id', new ArrayExpression([[1,2], [3,4]], 'float', 2)],
                    '"id" @> ARRAY[ARRAY[:qp0, :qp1]::float[], ARRAY[:qp2, :qp3]::float[]\\]::float[][]',
                    [':qp0' => 1, ':qp1' => 2, ':qp2' => 3, ':qp3' => 4],
                ],
                [['@>', 'id', new ArrayExpression([])], '"id" @> ARRAY[]', []],
                'array can contain nulls' => [
                    ['@>', 'id', new ArrayExpression([null])], '"id" @> ARRAY[:qp0]', [':qp0' => null],
                ],
                'traversable objects are supported' => [
                    ['@>', 'id', new ArrayExpression(new TraversableObject([1, 2, 3]))],
                    '[[id]] @> ARRAY[:qp0, :qp1, :qp2]',
                    [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
                ],
                [['@>', 'time', new ArrayExpression([new Expression('now()')])], '[[time]] @> ARRAY[now()]', []],
                [
                    [
                        '@>',
                        'id',
                        new ArrayExpression(
                            (new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1])
                        ),
                    ],
                    '[[id]] @> ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                    [':qp0' => 1],
                ],
                [
                    [
                        '@>',
                        'id',
                        new ArrayExpression(
                            [
                                (new Query(self::getDb()))->select('id')->from('users')->where(['active' => 1]),
                            ],
                            'integer'
                        ),
                    ],
                    '[[id]] @> ARRAY[ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)::integer[]]::integer[]',
                    [':qp0' => 1],
                ],

                /* json conditions */
                [
                    ['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])],
                    '[[jsoncol]] = :qp0',
                    [':qp0' => '{"lang":"uk","country":"UA"}'],
                ],
                [
                    ['=', 'jsoncol', new JsonExpression([false])],
                    '[[jsoncol]] = :qp0', [':qp0' => '[false]'],
                ],
                [
                    ['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                    '[[prices]] = :qp0::jsonb', [':qp0' => '{"seeds":15,"apples":25}'],
                ],
                'nested json' => [
                    [
                        '=',
                        'data',
                        new JsonExpression(
                            [
                                'user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'],
                                'props' => ['mood' => 'good'],
                            ]
                        ),
                    ],
                    '"data" = :qp0',
                    [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
                ],
                'null value' => [['=', 'jsoncol', new JsonExpression(null)], '"jsoncol" = :qp0', [':qp0' => 'null']],
                'null as array value' => [
                    ['=', 'jsoncol', new JsonExpression([null])], '"jsoncol" = :qp0', [':qp0' => '[null]'],
                ],
                'null as object value' => [
                    ['=', 'jsoncol', new JsonExpression(['nil' => null])], '"jsoncol" = :qp0', [':qp0' => '{"nil":null}'],
                ],
                'query' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            (new Query(self::getDb()))->select('params')->from('user')->where(['id' => 1])
                        ),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)',
                    [':qp0' => 1],
                ],
                'query with type' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            (new Query(self::getDb()))->select('params')->from('user')->where(['id' => 1]),
                            'jsonb'
                        ),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)::jsonb',
                    [':qp0' => 1],
                ],
                'array of json expressions' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression(
                            [new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonExpression([true])]
                        ),
                    ],
                    '"colname" = ARRAY[:qp0, :qp1]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]'],
                ],
                'Items in ArrayExpression of type json should be casted to Json' => [
                    ['=', 'colname', new ArrayExpression([['a' => null, 'b' => 123, 'c' => [4, 5]], [true]], SchemaInterface::TYPE_JSON)],
                    '"colname" = ARRAY[:qp0, :qp1]::json[]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]'],
                ],
                'Two dimension array of text' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression([['text1', 'text2'], ['text3', 'text4'], [null, 'text5']], 'text', 2),
                    ],
                    '"colname" = ARRAY[ARRAY[:qp0, :qp1]::text[], ARRAY[:qp2, :qp3]::text[], ARRAY[:qp4, :qp5]::text[]]::text[][]',
                    [
                        ':qp0' => 'text1',
                        ':qp1' => 'text2',
                        ':qp2' => 'text3',
                        ':qp3' => 'text4',
                        ':qp4' => null,
                        ':qp5' => 'text5',
                    ],
                ],
                'Three dimension array of booleans' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression([[[true], [false, null]], [[false], [true], [false]], [['t', 'f']]], 'bool', 3),
                    ],
                    '"colname" = ARRAY[ARRAY[ARRAY[:qp0]::bool[], ARRAY[:qp1, :qp2]::bool[]]::bool[][], ARRAY[ARRAY[:qp3]::bool[], ARRAY[:qp4]::bool[], ARRAY[:qp5]::bool[]]::bool[][], ARRAY[ARRAY[:qp6, :qp7]::bool[]]::bool[][]]::bool[][][]',
                    [
                        ':qp0' => true,
                        ':qp1' => false,
                        ':qp2' => null,
                        ':qp3' => false,
                        ':qp4' => true,
                        ':qp5' => false,
                        ':qp6' => 't',
                        ':qp7' => 'f',
                    ],
                ],

                /* Checks to verity that operators work correctly */
                [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
                [['<@', 'id', new ArrayExpression([1])], '"id" <@ ARRAY[:qp0]', [':qp0' => 1]],
                [['=', 'id',  new ArrayExpression([1])], '"id" = ARRAY[:qp0]', [':qp0' => 1]],
                [['<>', 'id', new ArrayExpression([1])], '"id" <> ARRAY[:qp0]', [':qp0' => 1]],
                [['>', 'id',  new ArrayExpression([1])], '"id" > ARRAY[:qp0]', [':qp0' => 1]],
                [['<', 'id',  new ArrayExpression([1])], '"id" < ARRAY[:qp0]', [':qp0' => 1]],
                [['>=', 'id', new ArrayExpression([1])], '"id" >= ARRAY[:qp0]', [':qp0' => 1]],
                [['<=', 'id', new ArrayExpression([1])], '"id" <= ARRAY[:qp0]', [':qp0' => 1]],
                [['&&', 'id', new ArrayExpression([1])], '"id" && ARRAY[:qp0]', [':qp0' => 1]],

                /* structured conditions */
                'structured without type' => [
                    ['=', 'price_col', new StructuredExpression(['value' => 10, 'currency_code' => 'USD'])],
                    '[[price_col]] = ROW(:qp0, :qp1)',
                    [':qp0' => 10, ':qp1' => 'USD'],
                ],
                'structured with type' => [
                    ['=', 'price_col', new StructuredExpression(['value' => 10, 'currency_code' => 'USD'], 'currency_money_structured')],
                    '[[price_col]] = ROW(:qp0, :qp1)::currency_money_structured',
                    [':qp0' => 10, ':qp1' => 'USD'],
                ],
                'structured with columns' => [
                    ['=', 'price_col', new StructuredExpression(['value' => 10, 'currency_code' => 'USD'], 'currency_money_structured', $priceColumns)],
                    '[[price_col]] = ROW(:qp0, :qp1)::currency_money_structured',
                    [':qp0' => 10.0, ':qp1' => 'USD'],
                ],
                'scalar can not be converted to structured' => [['=', 'price_col', new StructuredExpression(1)], '"price_col" = NULL', []],
                'array of structured' => [
                    ['=', 'price_array', new ArrayExpression(
                        [
                            null,
                            new StructuredExpression(['value' => 11.11, 'currency_code' => 'USD']),
                            new StructuredExpression(['value' => null, 'currency_code' => null]),
                        ]
                    )],
                    '"price_array" = ARRAY[:qp0, ROW(:qp1, :qp2), ROW(:qp3, :qp4)]',
                    [':qp0' => null, ':qp1' => 11.11, ':qp2' => 'USD', ':qp3' => null, ':qp4' => null],
                ],
                'structured null value' => [['=', 'price_col', new StructuredExpression(null)], '"price_col" = NULL', []],
                'structured null values' => [
                    ['=', 'price_col', new StructuredExpression([null, null])], '"price_col" = ROW(:qp0, :qp1)', [':qp0' => null, ':qp1' => null],
                ],
                'structured query' => [
                    ['=', 'price_col', new StructuredExpression(
                        (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1])
                    )],
                    '[[price_col]] = (SELECT [[price]] FROM [[product]] WHERE [[id]]=:qp0)',
                    [':qp0' => 1],
                ],
                'structured query with type' => [
                    [
                        '=',
                        'price_col',
                        new StructuredExpression(
                            (new Query(self::getDb()))->select('price')->from('product')->where(['id' => 1]),
                            'currency_money_structured'
                        ),
                    ],
                    '[[price_col]] = (SELECT [[price]] FROM [[product]] WHERE [[id]]=:qp0)::currency_money_structured',
                    [':qp0' => 1],
                ],
                'traversable objects are supported in structured' => [
                    ['=', 'price_col', new StructuredExpression(new TraversableObject([10, 'USD']))],
                    '[[price_col]] = ROW(:qp0, :qp1)',
                    [':qp0' => 10, ':qp1' => 'USD'],
                ],
            ]
        );
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function insertWithReturningPks(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4) RETURNING "id"
                SQL,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ("related_id", "time") VALUES (:qp0, now())
                SQL,
                [':qp0' => null],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id", "col") VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query(self::getDb()))
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                            'name' => 'sergeymakinen',
                            'address' => '{{city}}',
                            'is_active' => false,
                            'related_id' => null,
                            'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                        ],
                    ),
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") SELECT "email", "name", "address", "is_active", "related_id" FROM "customer" WHERE ("email"=:qp1) AND ("name"=:qp2) AND ("address"=:qp3) AND ("is_active"=:qp4) AND ("related_id" IS NULL) AND ("col"=CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                <<<SQL
                INSERT INTO {{%order_item}} ("order_id", "item_id", "quantity", "subtotal") VALUES (:qp0, :qp1, :qp2, :qp3) RETURNING "order_id", "item_id"
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => 1.0,],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"',
            ],
            'regular values with unique at not the first position' => [
                3 => 'INSERT INTO "T_upsert" ("address", "email", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"',
            ],
            'regular values with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"="T_upsert"."orders" + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING',
            ],
            'query' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"',
            ],
            'query with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1',
            ],
            'query without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING',
            ],
            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ' .
                    'ON CONFLICT ("email") DO UPDATE SET "ts"=EXCLUDED."ts"',
            ],
            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                2 => ['[[orders]]' => new Expression('EXCLUDED.orders + 1')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ' .
                    'ON CONFLICT ("email") DO UPDATE SET "orders"=EXCLUDED.orders + 1',
            ],
            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ON CONFLICT DO NOTHING',
            ],
            'query, values and expressions with update part' => [
                1 => (new Query(self::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                2 => ['ts' => 0, '[[orders]]' => new Expression('EXCLUDED.orders + 1')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ' .
                    'ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, "orders"=EXCLUDED.orders + 1',
            ],
            'query, values and expressions without update part' => [
                1 => (new Query(self::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ON CONFLICT DO NOTHING',
            ],
            'no columns to update' => [
                3 => 'INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
            'no columns to update with unique' => [
                3 => 'INSERT INTO {{%T_upsert}} ("email") VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ("type") VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        $upsert['table view'] = [
            'animal_view',
            ['id' => 3, 'type' => 'yiiunit\data\ar\Mouse'],
            true,
            'INSERT INTO "animal_view" ("id", "type") VALUES (:qp0, :qp1) ON CONFLICT ("id") DO UPDATE SET "type"=EXCLUDED."type"',
            [':qp0' => 3, ':qp1' => 'yiiunit\data\ar\Mouse'],
        ];

        return $upsert;
    }
}
