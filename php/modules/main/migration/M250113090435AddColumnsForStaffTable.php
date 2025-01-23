<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M250113090435AddColumnsForStaffTable
 */
final class M250113090435AddColumnsForStaffTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
alter table main.staff add column enable_archive boolean not null default false;
comment on column main.staff.enable_archive is '是否开启了会话存档开关';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        migrate_exec($b, "alter table main.staff drop column enable_archive");
    }
}
