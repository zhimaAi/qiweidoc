<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Class M241118081453CreateCustomerTagTable
 */
final class M241118081453CreateCustomerTagTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ <<<SQL
create table main.customer_tag
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(32) not null default '',
    tag_id varchar(255) not null default '',
    external_userid roaringbitmap null
);

create index on main.customer_tag using btree ("corp_id", "tag_id");

comment on table main.customer_tag is '客户标签表';

comment on column main.customer_tag.corp_id is '企业id';
comment on column main.customer_tag.tag_id is '标签id';
comment on column main.customer_tag.external_userid is '由当前标签的客户id集合';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table main.customer_tag";

        migrate_exec($b, $sql);
    }
}
