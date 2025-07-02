<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper\Handler;

use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\VarDumper\VarDumper;

final class EchoHandler implements HandlerInterface
{
    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $varDumper = VarDumper::create($variable);
        $output = $varDumper->asString($depth);

        if ($highlight) {
            $result = highlight_string("<?php\n" . $output, true);
            $output = preg_replace('/&lt;\\?php<br \\/>/', '', $result, 1);
        }

        echo $output;
    }
}
