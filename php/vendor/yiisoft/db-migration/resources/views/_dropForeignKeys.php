<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $foreignKeys \Yiisoft\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

foreach ($foreignKeys as $foreignKey) {
    echo "        // drops foreign key for table `{$foreignKey->getRelatedTable()}`\n";
    echo "        \$b->dropForeignKey(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getForeignKeyName()}'\n";
    echo "        );\n";
    echo "\n";
    echo "        // drops index for column `{$foreignKey->getColumn()}`\n";
    echo "        \$b->dropIndex(\n";
    echo "            '$table',\n";
    echo "            '{$foreignKey->getIndexName()}'\n";
    echo "        );\n";
    echo "\n";
}
