<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M251218071016AlterColumnForDepartmentTable
 */
final class M251218071016AlterColumnForDepartmentTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
alter table main.department alter column "order" type int8;
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        migrate_exec($b, 'alter table main.department alter column "order" type int4');
    }
}
