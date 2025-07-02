<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $columns \Yiisoft\Db\Migration\Service\Generate\Column[] Fields.
 * @var $foreignKeys \Yiisoft\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

echo $this->render(__DIR__ . '/_dropForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);

foreach ($columns as $column) {
    echo "        \$b->dropColumn('$table', '{$column->getName()}');\n";
}
