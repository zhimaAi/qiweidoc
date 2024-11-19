<?php

declare(strict_types=1);

namespace App\Migrations;

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
create table customer_tag
(
    id serial primary key,
    created_at timestamp not null default now(),
    updated_at timestamp not null default now(),

    corp_id varchar(32) not null default '',
    tag_id varchar(255) not null default '',
    name varchar(255) not null default '',
    tag_create_time timestamp not null default now(),
    "order" int4 not null default 0,
    tag_type int2 not null default 1,
    group_id varchar(255) not null default '',
    external_userid roaringbitmap null
);

create index on customer_tag using btree ("corp_id", "tag_id");

comment on table customer_tag is '客户标签表';

comment on column customer_tag.corp_id is '企业id';
comment on column customer_tag.tag_id is '标签id';
comment on column customer_tag.name is '标签名';
comment on column customer_tag.tag_create_time is '标签创建时间';
comment on column customer_tag.order is '排序字段';
comment on column customer_tag.tag_type is '标签类型，1:标签组，2:标签';
comment on column customer_tag.group_id is '所属标签组ID';
comment on column customer_tag.external_userid is '由当前标签的客户id集合';
SQL;

        migrate_exec($b, $sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $sql = /** @lang sql */ "drop table customer_tag";

        migrate_exec($b, $sql);
    }
}
