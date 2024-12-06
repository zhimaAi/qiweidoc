<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241122094059AddColumnsForCorpsTable
 */
final class M241122094059AddColumnsForCorpsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql =  /** @lang sql */ <<<SQL
alter table main.corps
    add column callback_event_token varchar(32) not null default '',
    add column callback_event_aes_key char(43) not null default '';

comment on column main.corps.callback_event_token is '回调事件加密解密用';
comment on column main.corps.callback_event_aes_key is '回调事件加密解密用';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
alter table main.corps
    drop column callback_event_token,
    drop column callback_event_aes_key;
SQL;

        migrate_exec($b, $sql);
    }
}
