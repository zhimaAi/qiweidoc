<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241211111149AddColumnFroUsers
 */
final class M241211111149AddColumnFroUsers implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {

        $sql =  /** @lang sql */ <<<SQL
-- 添加字段到用户表
ALTER TABLE main.users
  ADD COLUMN role_id int2 NOT NULL DEFAULT 1,
  ADD COLUMN exp_time int4 NOT NULL DEFAULT 0,
  ADD COLUMN description varchar(191) NOT NULL DEFAULT '',
  ADD COLUMN can_login int2 NOT NULL DEFAULT 1;

-- 添加备注信息
COMMENT ON COLUMN main.users.role_id IS '用户角色';
COMMENT ON COLUMN main.users.exp_time IS '过期时间，0:不过期，非0：过期时间戳';
COMMENT ON COLUMN main.users.can_login IS '是否可以登陆后台，0:不可以，1：可以';
COMMENT ON COLUMN main.users.description IS '备注信息';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
-- 删除字段
ALTER TABLE main.users
    DROP COLUMN role_id,
    DROP COLUMN exp_time,
    DROP COLUMN description,
    DROP COLUMN can_login;
SQL;

        migrate_exec($b, $sql);
    }
}
