<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $table string
 * @var $columns \Yiisoft\Db\Migration\Service\Generate\Column[] Fields.
 * @var $foreignKeys \Yiisoft\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

foreach ($columns as $column) {
    echo "        \$b->addColumn('$table', '{$column->getName()}', \$b->{$column->getDecoratorsString()});\n";
}

echo $this->render(__DIR__ . '/_addForeignKeys.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
