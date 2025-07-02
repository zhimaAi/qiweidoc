<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;

final class Migrator
{
    private bool $checkMigrationHistoryTable = true;

    public function __construct(
        private ConnectionInterface $db,
        private MigrationInformerInterface $informer,
        private string $historyTable = '{{%migration}}',
        private ?int $migrationNameLimit = 180,
        private ?int $maxSqlOutputLength = null,
    ) {
    }

    public function setIo(?SymfonyStyle $io): void
    {
        $this->informer->setIo($io);
    }

    public function up(MigrationInterface $migration): void
    {
        $this->checkMigrationHistoryTable();

        match ($migration instanceof TransactionalMigrationInterface) {
            true => $this->db->transaction(fn () => $migration->up($this->createBuilder())),
            false => $migration->up($this->createBuilder()),
        };

        $this->addMigrationToHistory($migration);
    }

    public function down(RevertibleMigrationInterface $migration): void
    {
        $this->checkMigrationHistoryTable();

        match ($migration instanceof TransactionalMigrationInterface) {
            true => $this->db->transaction(fn () => $migration->down($this->createBuilder())),
            false => $migration->down($this->createBuilder()),
        };

        $this->removeMigrationFromHistory($migration);
    }

    public function getMigrationNameLimit(): ?int
    {
        if ($this->migrationNameLimit !== null) {
            return $this->migrationNameLimit;
        }

        $tableSchema = $this->db->getSchema()->getTableSchema($this->historyTable);

        if ($tableSchema === null) {
            return null;
        }

        $limit = $tableSchema->getColumns()['name']->getSize();

        if ($limit === null) {
            return null;
        }

        return $this->migrationNameLimit = $limit;
    }

    /**
     * @psalm-return array<class-string, int|string>
     */
    public function getHistory(?int $limit = null): array
    {
        $this->checkMigrationHistoryTable();

        $query = (new Query($this->db))
            ->select(['apply_time', 'name'])
            ->from($this->historyTable)
            ->orderBy(['apply_time' => SORT_DESC, 'id' => SORT_DESC])
            ->indexBy('name');

        if ($limit > 0) {
            $query->limit($limit);
        }

        /** @psalm-var array<class-string, int|string> */
        return $query->column();
    }

    public function getHistoryTable(): string
    {
        return $this->historyTable;
    }

    private function addMigrationToHistory(MigrationInterface $migration): void
    {
        $this->db->createCommand()->insert(
            $this->historyTable,
            [
                'name' => $this->getMigrationName($migration),
                'apply_time' => time(),
            ]
        )->execute();
    }

    private function removeMigrationFromHistory(MigrationInterface $migration): void
    {
        $command = $this->db->createCommand();
        $command->delete($this->historyTable, [
            'name' => $this->getMigrationName($migration),
        ])->execute();
    }

    private function getMigrationName(MigrationInterface $migration): string
    {
        return $migration::class;
    }

    private function checkMigrationHistoryTable(): void
    {
        if (!$this->checkMigrationHistoryTable) {
            return;
        }

        if ($this->db->getSchema()->getTableSchema($this->historyTable, true) === null) {
            $this->createMigrationHistoryTable();
        }

        $this->checkMigrationHistoryTable = false;
    }

    private function createMigrationHistoryTable(): void
    {
        /**
         * Remove these annotations after raise Yii DB version to 2.0
         *
         * @psalm-suppress UndefinedInterfaceMethod
         * @var string $tableName
         */
        $tableName = $this->db->getQuoter()->getRawTableName($this->historyTable);
        $this->informer->beginCreateHistoryTable('Creating migration history table "' . $tableName . '"...');

        $b = $this->createBuilder(new NullMigrationInformer());

        $b->createTable($this->historyTable, [
            'id' => $b->primaryKey(),
            'name' => $b->string($this->migrationNameLimit)->notNull(),
            'apply_time' => $b->integer()->notNull(),
        ]);

        $this->informer->endCreateHistoryTable('Done.');
    }

    private function createBuilder(?MigrationInformerInterface $informer = null): MigrationBuilder
    {
        return new MigrationBuilder(
            $this->db,
            $informer ?? $this->informer,
            $this->maxSqlOutputLength,
        );
    }
}
