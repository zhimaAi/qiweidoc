<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoConnectionTest;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoConnectionTest extends CommonPdoConnectionTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertID(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert(
            'customer',
            [
                'name' => 'Some {{weird}} name',
                'email' => 'test@example.com',
                'address' => 'Some {{%weird}} address',
            ]
        )->execute();

        $this->assertSame('4', $db->getLastInsertID('public.customer_id_seq'));

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidCallException
     * @throws Throwable
     */
    public function testGetLastInsertIDWithException(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('item', ['name' => 'Yii2 starter', 'category_id' => 1])->execute();
        $command->insert('item', ['name' => 'Yii3 starter', 'category_id' => 1])->execute();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PostgreSQL not support lastInsertId without sequence name.');

        $db->getLastInsertID();

        $db->close();
    }
}
