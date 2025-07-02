<?php

declare(strict_types=1);

/**
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $foreignKeys \Yiisoft\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

if (!empty($foreignKeys)) {
    echo " * Has foreign keys to the tables:\n";
    echo " *\n";
    foreach ($foreignKeys as $foreignKey) {
        echo " * - `{$foreignKey->getRelatedTable()}`\n";
    }
}
