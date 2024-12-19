<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241211111213AddColumnFroStaff
 */
final class M241211111213AddColumnFroStaff implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql =  /** @lang sql */ <<<SQL
-- 添加字段到用户表
ALTER TABLE main.staff
  ADD COLUMN role_id int2 NOT NULL DEFAULT 1,
  ADD COLUMN can_login int2 NOT NULL DEFAULT 1;

-- 添加备注信息
COMMENT ON COLUMN main.staff.role_id IS '用户角色';
COMMENT ON COLUMN main.staff.can_login IS '是否可以登陆后台，0:不可以，1：可以';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
-- 删除收藏字段
ALTER TABLE main.staff
    DROP COLUMN role_id,
    DROP COLUMN can_login;
SQL;

        migrate_exec($b, $sql);
    }
}
