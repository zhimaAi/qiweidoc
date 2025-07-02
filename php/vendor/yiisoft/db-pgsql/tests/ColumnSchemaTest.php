<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\ColumnSchema;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaInterface;

use function stream_get_contents;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');
        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'bool_col' => false,
                'bit_col' => 0b0110_0100, // 100
                'varbit_col' => 0b1_1100_1000, // 456
                'bigint_col' => 9_223_372_036_854_775_806,
                'intarray_col' => [1, -2, null, '42'],
                'numericarray_col' => [null, 1.2, -2.2, null, null],
                'varchararray_col' => ['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null],
                'textarray2_col' => new ArrayExpression(null),
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
                'jsonb_col' => new JsonExpression(new ArrayExpression([1, 2, 3])),
                'jsonarray_col' => [new ArrayExpression([[',', 'null', true, 'false', 'f']], SchemaInterface::TYPE_JSON)],
            ]
        );
        $command->execute();
        $query = (new Query($db))->from('type')->one();

        $this->assertNotNull($tableSchema);

        $intColPhpTypeCast = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpTypeCast = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $floatColPhpTypeCast = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpTypeCast = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $boolColPhpTypeCast = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $bitColPhpTypeCast = $tableSchema->getColumn('bit_col')?->phpTypecast($query['bit_col']);
        $varbitColPhpTypeCast = $tableSchema->getColumn('varbit_col')?->phpTypecast($query['varbit_col']);
        $numericColPhpTypeCast = $tableSchema->getColumn('numeric_col')?->phpTypecast($query['numeric_col']);
        $intArrayColPhpType = $tableSchema->getColumn('intarray_col')?->phpTypecast($query['intarray_col']);
        $numericArrayColPhpTypeCast = $tableSchema->getColumn('numericarray_col')?->phpTypecast($query['numericarray_col']);
        $varcharArrayColPhpTypeCast = $tableSchema->getColumn('varchararray_col')?->phpTypecast($query['varchararray_col']);
        $textArray2ColPhpType = $tableSchema->getColumn('textarray2_col')?->phpTypecast($query['textarray2_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);
        $jsonBColPhpType = $tableSchema->getColumn('jsonb_col')?->phpTypecast($query['jsonb_col']);
        $jsonArrayColPhpType = $tableSchema->getColumn('jsonarray_col')?->phpTypecast($query['jsonarray_col']);

        $this->assertSame(1, $intColPhpTypeCast);
        $this->assertSame(str_repeat('x', 100), $charColPhpTypeCast);
        $this->assertSame(1.234, $floatColPhpTypeCast);
        $this->assertSame("\x10\x11\x12", stream_get_contents($blobColPhpTypeCast));
        $this->assertFalse($boolColPhpTypeCast);
        $this->assertSame(0b0110_0100, $bitColPhpTypeCast);
        $this->assertSame(0b1_1100_1000, $varbitColPhpTypeCast);
        $this->assertSame(33.22, $numericColPhpTypeCast);
        $this->assertSame([1, -2, null, 42], $intArrayColPhpType);
        $this->assertSame([null, 1.2, -2.2, null, null], $numericArrayColPhpTypeCast);
        $this->assertSame(['', 'some text', '""', '\\\\', '[",","null",true,"false","f"]', null], $varcharArrayColPhpTypeCast);
        $this->assertNull($textArray2ColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);
        $this->assertSame(['1', '2', '3'], $jsonBColPhpType);
        $this->assertSame([[[',', 'null', true, 'false', 'f']]], $jsonArrayColPhpType);

        $db->close();
    }

    /**
     * @throws JsonException
     */
    public function testPhpTypeCastBool(): void
    {
        $columnSchema = new ColumnSchema('boolean');

        $columnSchema->type('boolean');

        $this->assertFalse($columnSchema->phpTypeCast('f'));
        $this->assertTrue($columnSchema->phpTypeCast('t'));
    }

    public function testDbTypeCastJson(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertEquals(new JsonExpression('', 'json'), $tableSchema->getColumn('json_col')->dbTypecast(''));
        $this->assertEquals(new JsonExpression('', 'jsonb'), $tableSchema->getColumn('jsonb_col')->dbTypecast(''));
    }

    public function testBoolDefault(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('bool_values');
        $command->insert('bool_values', ['id' => new Expression('DEFAULT')]);
        $command->execute();
        $query = (new Query($db))->from('bool_values')->one();

        $this->assertNull($query['bool_col']);
        $this->assertTrue($query['default_true']);
        $this->assertTrue($query['default_qtrueq']);
        $this->assertTrue($query['default_t']);
        $this->assertTrue($query['default_yes']);
        $this->assertTrue($query['default_on']);
        $this->assertTrue($query['default_1']);
        $this->assertFalse($query['default_false']);
        $this->assertFalse($query['default_qfalseq']);
        $this->assertFalse($query['default_f']);
        $this->assertFalse($query['default_no']);
        $this->assertFalse($query['default_off']);
        $this->assertFalse($query['default_0']);
        $this->assertSame(
            [null, true, true, true, true, true, true, false, false, false, false, false, false],
            $tableSchema->getColumn('default_array')->phpTypecast($query['default_array'])
        );

        $this->assertNull($tableSchema->getColumn('bool_col')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_true')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_qtrueq')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_t')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_yes')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_on')->getDefaultValue());
        $this->assertTrue($tableSchema->getColumn('default_1')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_false')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_qfalseq')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_f')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_no')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_off')->getDefaultValue());
        $this->assertFalse($tableSchema->getColumn('default_0')->getDefaultValue());
        $this->assertSame(
            [null, true, true, true, true, true, true, false, false, false, false, false, false],
            $tableSchema->getColumn('default_array')->getDefaultValue()
        );

        $db->close();
    }

    public function testNegativeDefaultValues()
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('negative_default_values');

        $this->assertSame(-123, $tableSchema->getColumn('tinyint_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('smallint_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('int_col')->getDefaultValue());
        $this->assertSame(-123, $tableSchema->getColumn('bigint_col')->getDefaultValue());
        $this->assertSame(-12345.6789, $tableSchema->getColumn('float_col')->getDefaultValue());
        $this->assertSame(-33.22, $tableSchema->getColumn('numeric_col')->getDefaultValue());
    }

    public function testDbTypeCastBit()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertSame('01100100', $tableSchema->getColumn('bit_col')->dbTypecast('01100100'));
    }

    public function testPrimaryKeyOfView()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('T_constraints_2_view');

        $this->assertSame(['C_id_1', 'C_id_2'], $tableSchema->getPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_1')->isPrimaryKey());
        $this->assertTrue($tableSchema->getColumn('C_id_2')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_1')->isPrimaryKey());
        $this->assertFalse($tableSchema->getColumn('C_index_2_2')->isPrimaryKey());
    }

    public function testStructuredType(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('test_structured_type');

        $command->insert('test_structured_type', [
            'price_col' => ['value' => 10.0, 'currency_code' => 'USD'],
            'price_array' => [
                null,
                ['value' => 11.11, 'currency_code' => 'USD'],
                ['value' => null, 'currency_code' => null],
            ],
            'price_array2' => [[
                ['value' => 123.45, 'currency_code' => 'USD'],
            ]],
            'range_price_col' => [
                'price_from' => ['value' => 1000.0, 'currency_code' => 'USD'],
                'price_to' => ['value' => 2000.0, 'currency_code' => 'USD'],
            ],
        ])->execute();

        $query = (new Query($db))->from('test_structured_type')->one();

        $priceColPhpType = $tableSchema->getColumn('price_col')->phpTypecast($query['price_col']);
        $priceDefaultPhpType = $tableSchema->getColumn('price_default')->phpTypecast($query['price_default']);
        $priceArrayPhpType = $tableSchema->getColumn('price_array')->phpTypecast($query['price_array']);
        $priceArray2PhpType = $tableSchema->getColumn('price_array2')->phpTypecast($query['price_array2']);
        $rangePriceColPhpType = $tableSchema->getColumn('range_price_col')->phpTypecast($query['range_price_col']);

        $this->assertSame(['value' => 10.0, 'currency_code' => 'USD'], $priceColPhpType);
        $this->assertSame(['value' => 5.0, 'currency_code' => 'USD'], $priceDefaultPhpType);
        $this->assertSame(
            [
                null,
                ['value' => 11.11, 'currency_code' => 'USD'],
                ['value' => null, 'currency_code' => null],
            ],
            $priceArrayPhpType
        );
        $this->assertSame(
            [[
                ['value' => 123.45, 'currency_code' => 'USD'],
            ]],
            $priceArray2PhpType
        );
        $this->assertSame(
            [
                'price_from' => ['value' => 1000.0, 'currency_code' => 'USD'],
                'price_to' => ['value' => 2000.0, 'currency_code' => 'USD'],
            ],
            $rangePriceColPhpType
        );

        $priceCol = $tableSchema->getColumn('price_col');
        $this->assertNull($priceCol->phpTypecast(1), 'For scalar value returns `null`');

        $priceCol->columns([]);
        $this->assertSame([5, 'USD'], $priceCol->phpTypecast([5, 'USD']), 'No type casting for empty columns');

        $priceArray = $tableSchema->getColumn('price_array');
        $this->assertEquals(
            new ArrayExpression([], 'currency_money_structured', 1),
            $priceArray->dbTypecast(1),
            'For scalar value returns empty array'
        );

        $priceArray2 = $tableSchema->getColumn('price_array2');
        $this->assertEquals(
            new ArrayExpression([null, null], 'currency_money_structured', 2),
            $priceArray2->dbTypecast([null, null]),
            'Double array of null values'
        );

        $db->close();
    }
}
