<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
    ]);

    $rectorConfig->skip([
        ClosureToArrowFunctionRector::class,
        JsonThrowOnErrorRector::class,
        StringClassNameToClassConstantRector::class => [
            __DIR__ . '/tests/UseStatementParserTest.php',
        ],
        ClassConstantToSelfClassRector::class => [
            __DIR__ . '/tests/TestAsset/DummyIteratorAggregateWithClosure.php',
        ],
    ]);
};
