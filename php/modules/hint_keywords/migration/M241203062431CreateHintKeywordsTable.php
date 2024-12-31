<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241203062431CreateHintKeywordsTable
 */
final class M241203062431CreateHintKeywordsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<EOF
create table hint_keywords.hint_keywords (
    id serial primary key,
    corp_id varchar(64) not null default '',
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),
    group_name varchar(64) not null default '',
    keywords  jsonb not null default '[]',
    create_user_id varchar(64) not null default ''
);

comment on table hint_keywords.hint_keywords is '敏感词列表';

comment on column hint_keywords.hint_keywords.corp_id is '企业ID';
comment on column hint_keywords.hint_keywords.group_name is '敏感词组名称';
comment on column hint_keywords.hint_keywords.keywords is '敏感词内容';
comment on column hint_keywords.hint_keywords.create_user_id is '创建人用户ID';

EOF;

        migrate_exec($b, $sql);

    }

    public function down(MigrationBuilder $b): void
    {

        $sql = /** @lang sql */ "drop table hint_keywords.hint_keywords";

        migrate_exec($b, $sql);
    }
}
