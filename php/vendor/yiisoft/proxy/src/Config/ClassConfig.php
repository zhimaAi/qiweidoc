<?php

declare(strict_types=1);

namespace Yiisoft\Proxy\Config;

/**
 * @internal
 *
 * A class / an interface metadata. {@see ClassConfigFactory} is used for creation.
 */
final class ClassConfig
{
    public function __construct(
        /**
         * Whether it's config for a class (`false` value) or an interface (`true` value).
         */
        public bool $isInterface,
        /**
         * @var string Namespace, for example: `Yiisoft\Proxy\Tests\Stub`.
         *
         * @link https://www.php.net/manual/en/language.namespaces.php
         */
        public string $namespace,
        /**
         * @var string[] A list of modifiers, for example: `['final']`.
         */
        public array $modifiers,
        /**
         * @var string Full name including namespace, for example: `Yiisoft\Proxy\Tests\Stub\GraphInterface`.
         */
        public string $name,
        /**
         * @var string Short name without namespace, for example: `GraphInterface`.
         */
        public string $shortName,
        /**
         * @var string Full parent class name including namespace. Empty string means class have no parent class.
         */
        public string $parent,
        /**
         * @var string[] A list of interfaces this class implements or interface extends from.
         */
        public array $interfaces,
        /**
         * @var MethodConfig[] A map where key is a {@see $name} and value is {@see MethodConfig} instance.
         * @psalm-var array<string, MethodConfig>
         */
        public array $methods,
    ) {
    }
}
