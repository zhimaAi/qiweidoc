<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class CommandPDOProvider extends \Yiisoft\Db\Tests\Provider\CommandPDOProvider
{
    public static function bindParam(): array
    {
        $bindParam = parent::bindParam();

        $bindParam[0][6] = [
            'id' => 1,
            'email' => 'user1@example.com',
            'name' => 'user1',
            'address' => 'address1',
            'status' => 1,
            'bool_status' => true,
            'profile_id' => 1,
        ];

        return $bindParam;
    }
}
