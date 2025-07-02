<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PDO;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionTest extends CommonConnectionTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testInitConnection(): void
    {
        $db = $this->getConnection();

        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertTrue($db->getEmulatePrepare());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testSettingDefaultAttributes(): void
    {
        $db = $this->getConnection();

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $db->getActivePDO()->getAttribute(PDO::ATTR_ERRMODE));

        $db->close();
        $db->setEmulatePrepare(true);
        $db->open();

        $this->assertEquals(true, $db->getActivePDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
        $db->setEmulatePrepare(false);
        $db->open();

        $this->assertEquals(false, $db->getActivePDO()->getAttribute(PDO::ATTR_EMULATE_PREPARES));

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testTransactionIsolation(): void
    {
        $db = $this->getConnection();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::READ_COMMITTED);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE);
        $transaction->commit();

        $transaction = $db->beginTransaction();
        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        /* should not be any exception so far */
        $this->assertTrue(true);

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTransactionShortcutCustom(): void
    {
        $db = $this->getConnection(true);

        $this->assertTrue(
            $db->transaction(
                static function (ConnectionInterface $db) {
                    $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                    return true;
                },
                TransactionInterface::READ_UNCOMMITTED,
            ),
            'transaction shortcut valid value should be returned from callback',
        );

        $this->assertEquals(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut'
                SQL,
            )->queryScalar(),
            'profile should be inserted in transaction shortcut',
        );

        $db->close();
    }
}
