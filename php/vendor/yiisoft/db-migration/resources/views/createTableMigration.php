<?php

declare(strict_types=1);

/**
 * This view is used by {@see Yiisoft\Db\Migration\Command\CreateCommand}.
 *
 * The following variables are available in this view:
 *
 * @var $this \Yiisoft\Db\Migration\Service\Generate\PhpRenderer
 * @var $className string The new migration class name without namespace.
 * @var $namespace string The new migration class namespace.
 * @var $table string The table name.
 * @var $tableComment string The table comment.
 * @var $columns \Yiisoft\Db\Migration\Service\Generate\Column[] Fields.
 * @var $foreignKeys \Yiisoft\Db\Migration\Service\Generate\ForeignKey[] Foreign keys.
 */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * Handles the creation of table `<?= $table ?>`.
<?= $this->render(__DIR__ . '/_foreignTables.php', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
final class <?= $className ?> implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_createTable.php', [
    'table' => $table,
    'columns' => $columns,
    'foreignKeys' => $foreignKeys,
])
?>
<?php if (!empty($tableComment)) {
    echo $this->render(__DIR__ . '/_addComments.php', [
        'table' => $table,
        'tableComment' => $tableComment,
    ]);
}
?>
    }

    public function down(MigrationBuilder $b): void
    {
<?= $this->render(__DIR__ . '/_dropTable.php', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
