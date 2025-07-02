<?php

declare(strict_types=1);

/**
 * Creates a call for the method `Yiisoft\Db\Migration\MigrationBuilder::createTable()`.
 *
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string The table name.
 * @var $columns \Yiisoft\Db\Migration\Service\Generate\Column[] Fields.
 * @var $foreignKeys array Foreign keys.
 */

echo "        \$b->createTable('$table', [\n";
foreach ($columns as $column) {
    if (!$column->hasDecorators()) {
        echo "            '{$column->getName()}',\n";
    } else {
        echo "            '{$column->getName()}' => \$b->{$column->getDecoratorsString()},\n";
    }
}
echo "        ]);\n";
echo $this->render(__DIR__ . '/_addForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
