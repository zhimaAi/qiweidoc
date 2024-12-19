<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241217081928AddColumnsForCorpTable
 */
final class M241217081928AddColumnsForCorpTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql =  /** @lang sql */ <<<SQL
-- 添加企业名称和logo
ALTER TABLE main.corps
  ADD COLUMN corp_name varchar(50) NOT NULL DEFAULT '',
  ADD COLUMN corp_logo varchar(300) NOT NULL DEFAULT '';

-- 添加备注信息
COMMENT ON COLUMN main.corps.corp_name IS '企业名称';
COMMENT ON COLUMN main.corps.corp_logo IS '企业logo';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        //
        $sql = /** @lang sql */ <<<SQL

-- 删除企业名称和logo
ALTER TABLE main.corps
    DROP COLUMN corp_name,
    DROP COLUMN corp_logo;
SQL;

        migrate_exec($b, $sql);

    }
}
