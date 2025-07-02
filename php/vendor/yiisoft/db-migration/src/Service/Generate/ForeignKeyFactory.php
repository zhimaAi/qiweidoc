<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;

use function count;

/**
 * @internal
 */
final class ForeignKeyFactory
{
    public function __construct(
        private ConnectionInterface $db,
        private ?SymfonyStyle $io,
        private bool $useTablePrefix
    ) {
    }

    public function create(
        string $table,
        string $column,
        string $relatedTable,
        string|null $relatedColumn
    ): ForeignKey {
        /**
         * We're trying to get it from the table schema.
         *
         * {@see https://github.com/yiisoft/yii2/issues/12748}
         */
        if ($relatedColumn === null) {
            $relatedColumn = 'id';
            $tablePrimaryKeys = $this->db->getSchema()->getTablePrimaryKey($relatedTable);

            if ($tablePrimaryKeys !== null) {
                $primaryKeys = (array) $tablePrimaryKeys->getColumnNames();

                match (count($primaryKeys)) {
                    1 => $relatedColumn = (string) $primaryKeys[0],
                    default => $this->io?->writeln(
                        "<fg=yellow> Related table for field \"$column\" exists, but primary key is composite. Default name \"id\" will be used for related field</>\n"
                    ),
                };
            } else {
                $this->io?->writeln(
                    "<fg=yellow> Related table for field \"$column\" exists, but does not have a primary key. Default name \"id\" will be used for related field.</>\n"
                );
            }
        }

        return new ForeignKey(
            "idx-$table-$column",
            "fk-$table-$column",
            $column,
            $this->useTablePrefix ? '{{%' . $relatedTable . '}}' : $relatedTable,
            $relatedColumn,
        );
    }
}
