<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandTest;
use Yiisoft\Db\Tests\Support\DbHelper;

use function serialize;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.'
        );

        $command->addDefaultValue('{{table}}', '{{name}}', 'column', 'value');

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::batchInsert
     *
     * @throws Throwable
     */
    public function testBatchInsert(
        string $table,
        array $columns,
        array $values,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $columns, $values, $expected, $expectedParams, $insertedRow);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBooleanValuesInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('{{bool_values}}', ['bool_col' => true]);

        $this->assertSame(1, $command->execute());

        $command = $db->createCommand();
        $command->insert('{{bool_values}}', ['bool_col' => false]);

        $this->assertSame(1, $command->execute());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[bool_values]] WHERE [[bool_col]] = TRUE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[bool_values]] WHERE [[bool_col]] = FALSE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBooleanValuesBatchInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->batchInsert('{{bool_values}}', ['bool_col'], [[true], [false]]);

        $this->assertSame(2, $command->execute());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM "bool_values" WHERE bool_col = TRUE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM "bool_values" WHERE bool_col = FALSE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDelete(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{customer}}', ['id' => 2])->execute();
        $chekSql = <<<SQL
        SELECT COUNT([[id]]) FROM [[customer]]
        SQL;
        $command->setSql($chekSql);

        $this->assertSame(2, $command->queryScalar());

        $command->delete('{{customer}}', ['id' => 3])->execute();
        $command->setSql($chekSql);

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::dropDefaultValue is not supported by PostgreSQL.'
        );

        $command->dropDefaultValue('{{table}}', '{{name}}');

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * {@link https://github.com/yiisoft/yii2/issues/15827}
     */
    public function testIssue15827(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $inserted = $command->insert(
            '{{array_and_json_types}}',
            [
                'jsonb_col' => new JsonExpression(['Solution date' => '13.01.2011']),
            ],
        )->execute();

        $this->assertSame(1, $inserted);

        $found = $command->setSql(
            <<<SQL
            SELECT *
            FROM [[array_and_json_types]]
            WHERE [[jsonb_col]] @> '{"Some not existing key": "random value"}'
            SQL,
        )->execute();

        $this->assertSame(0, $found);

        $found = $command->setSql(
            <<<SQL
            SELECT *
            FROM [[array_and_json_types]]
            WHERE [[jsonb_col]] @> '{"Solution date": "13.01.2011"}'
            SQL,
        )->execute();

        $this->assertSame(1, $found);
        $this->assertSame(1, $command->delete('{{array_and_json_types}}')->execute());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::rawSql
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * {@link https://github.com/yiisoft/yii2/issues/11498}
     */
    public function testSaveSerializedObject(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command = $command->insert(
            '{{type}}',
            [
                'int_col' => 1,
                'char_col' => 'serialize',
                'float_col' => 5.6,
                'bool_col' => true,
                'blob_col' => serialize($db),
            ],
        );

        $this->assertSame(1, $command->execute());

        $command->update('{{type}}', ['blob_col' => serialize($db)], ['char_col' => 'serialize']);

        $this->assertSame(1, $command->execute());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::update
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        string $expected
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::upsert
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testinsertWithReturningPksUuid(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $result = $command->insertWithReturningPks(
            '{{%table_uuid}}',
            [
                'col' => 'test',
            ],
        );

        $this->assertIsString($result['uuid']);

        // for example ['uuid' => 738146be-87b1-49f2-9913-36142fb6fcbe]
        $this->assertStringMatchesFormat('%s-%s-%s-%s-%s', $result['uuid']);

        $this->assertEquals(36, strlen($result['uuid']));

        $db->close();
    }

    public function testShowDatabases(): void
    {
        $dsn = new Dsn('pgsql', '127.0.0.1');
        $db = new Connection(new Driver($dsn->asString(), 'root', 'root'), DbHelper::getSchemaCache());

        $command = $db->createCommand();

        $this->assertSame('pgsql:host=127.0.0.1;dbname=postgres;port=5432', $db->getDriver()->getDsn());
        $this->assertSame(['yiitest'], $command->showDatabases());
    }
}
