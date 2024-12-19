<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241219030737AddColumnsTimeoutMessagesTable
 */
final class M241219030737AddColumnsTimeoutMessagesTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
alter table timeout_reply_single.timeout_messages add column rule_id int8 not null default 0;

comment on column timeout_reply_single.timeout_messages.rule_id is '关联的规则id';
SQL;
        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = <<<SQL
alter table timeout_reply_single.timeout_messages drop column rule_id;
SQL;
        migrate_exec($b, $sql);
    }
}
