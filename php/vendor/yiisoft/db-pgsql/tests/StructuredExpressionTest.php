<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\StructuredExpression;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;

/**
 * @group pgsql
 */
final class StructuredExpressionTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\StructuredTypeProvider::normolizedValues */
    public function testGetNormalizedValue(mixed $value, mixed $expected, array $columns): void
    {
        $structuredExpression = new StructuredExpression($value, 'currency_money_structured', $columns);
        $this->assertSame($expected, $structuredExpression->getNormalizedValue());
    }
}
