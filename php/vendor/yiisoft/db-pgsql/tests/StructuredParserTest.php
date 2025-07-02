<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\StructuredParser;

/**
 * @group pgsql
 */
final class StructuredParserTest extends TestCase
{
    public function testParser(): void
    {
        $parser = new StructuredParser();

        $this->assertSame([null], $parser->parse('()'));
        $this->assertSame([0 => null, 1 => null], $parser->parse('(,)'));
        $this->assertSame([0 => '10.0', 1 => 'USD'], $parser->parse('(10.0,USD)'));
        $this->assertSame([0 => '1', 1 => '-2', 2 => null, 3 => '42'], $parser->parse('(1,-2,,42)'));
        $this->assertSame([0 => ''], $parser->parse('("")'));
        $this->assertSame(
            [0 => '[",","null",true,"false","f"]'],
            $parser->parse('("[\",\",\"null\",true,\"false\",\"f\"]")')
        );

        // Default values can have any expressions
        $this->assertSame(null, $parser->parse("'(10.0,USD)::structured_type'"));
    }
}
