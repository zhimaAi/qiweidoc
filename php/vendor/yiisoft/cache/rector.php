<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        StaticDataProviderClassMethodRector::class,
        DataProviderAnnotationToAttributeRector::class,
    ])
    ->withSkip([
        NullToStrictStringFuncCallArgRector::class,
    ]);
