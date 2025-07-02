<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper;

interface HandlerInterface
{
    public function handle(mixed $variable, int $depth, bool $highlight = false): void;
}
