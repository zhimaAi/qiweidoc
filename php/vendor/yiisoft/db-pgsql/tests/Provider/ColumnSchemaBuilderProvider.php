<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'pgsql';

    public static function createColumnTypes(): array
    {
        $types = parent::createColumnTypes();

        $types['uuid'][0] = '"column" uuid';
        $types['uuid not null'][0] = '"column" uuid NOT NULL';

        $types['uuid with default'][0] = '"column" uuid DEFAULT \'875343b3-6bd0-4bec-81bb-aa68bb52d945\'';

        $types['uuid pk'][0] = '"column" uuid PRIMARY KEY';
        $types['uuid pk not null'][0] = '"column" uuid PRIMARY KEY NOT NULL';

        $types['uuid pk not null with default'][0] = '"column" uuid PRIMARY KEY NOT NULL DEFAULT uuid_generate_v4()';
        $types['uuid pk not null with default'][3] = [['notNull'],['defaultExpression', 'uuid_generate_v4()']];

        return $types;
    }
}
