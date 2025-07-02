<?php

declare(strict_types=1);

namespace Yiisoft\Proxy\Config;

/**
 * @internal
 *
 * A parameter metadata. {@see ClassConfigFactory} is used for creation. Note that it relies only on PHP type hints and
 * ignores PHPDoc completely.
 */
final class ParameterConfig
{
    public function __construct(
        /**
         * @var TypeConfig|null Type config. `null` means no type specified.
         */
        public ?TypeConfig $type,
        /**
         * @var string A name without dollar sign (`$`). For example: `previousNodesCount`.
         */
        public string $name,
        /**
         * @var bool Whether the default value available.
         *
         * @link https://www.php.net/manual/en/functions.arguments.php#functions.arguments.default
         */
        public bool $isDefaultValueAvailable,
        /**
         * @var bool|null Whether the default value refers to a constant (when {@see $isDefaultValueAvailable} is
         * `true`}). `null` means no default value specified ({@see $isDefaultValueAvailable} is `false`).
         *
         * @link https://www.php.net/manual/en/language.constants.syntax.php
         */
        public ?bool $isDefaultValueConstant,
        /**
         * @var string|null A constant name for default value (when it's specified, {@see $isDefaultValueAvailable} and
         * {@see $isDefaultValueConstant} must be `true` at the same time). `null` means no default value specified or
         * it's not a constant (either {@see $isDefaultValueAvailable} or {@see $isDefaultValueConstant} is `false`).
         *
         * @link https://www.php.net/manual/en/language.constants.syntax.php
         */
        public ?string $defaultValueConstantName,
        /**
         * @var mixed The actual value specified as default with corresponding type. `null` means no default value (only
         * when {@see $isDefaultValueAvailable} is `false`).
         */
        public mixed $defaultValue,
    ) {
    }

    /**
     * Whether a parameter has type.
     *
     * @return bool `true` if type specified and `false` otherwise.
     *
     * @psalm-assert-if-true TypeConfig $this->type
     */
    public function hasType(): bool
    {
        return $this->type !== null;
    }
}
