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
 */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class <?= $className . "\n" ?>
 */
final class <?= $className ?> implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

    }

    public function down(MigrationBuilder $b): void
    {

    }
}
