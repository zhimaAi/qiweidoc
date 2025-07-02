<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

final class QuoterProvider extends \Yiisoft\Db\Tests\Provider\QuoterProvider
{
    public static function tableNameParts(): array
    {
        return [
            ['', ''],
            ['[]', '[]'],
            ['animal', 'animal'],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', '[animal]', '[dbo]'],
            ['[other].[animal2]', '[animal2]', '[other]'],
            ['other.[animal2]', '[animal2]', 'other'],
            ['other.animal2', 'animal2', 'other'],
        ];
    }
}
