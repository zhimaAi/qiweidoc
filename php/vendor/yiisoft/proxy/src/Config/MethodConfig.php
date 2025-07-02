<?php

declare(strict_types=1);

namespace Yiisoft\Proxy\Config;

/**
 * @internal
 *
 * A method metadata. {@see ClassConfigFactory} is used for creation. Note that it relies only on PHP type hints and
 * ignores PHPDoc completely.
 */
final class MethodConfig
{
    public function __construct(
        /**
         * @var string[] A list of modifiers, for example: `['abstract', 'public']`.
         */
        public array $modifiers,
        /**
         * @var string A name without parentheses. For example: `getInstance`.
         */
        public string $name,
        /**
         * @var ParameterConfig[] A map where key is a {@see ParameterConfig::$name} and value is {@see ParameterConfig}
         * instance.
         * @psalm-var array<string, ParameterConfig>
         */
        public array $parameters,
        /**
         * @var TypeConfig|null Return type config. `null` means no return type specified.
         */
        public ?TypeConfig $returnType,
    ) {
    }

    /**
     * Whether a method has return type.
     *
     * @return bool `true` if return type specified and `false` otherwise.
     *
     * @psalm-assert-if-true TypeConfig $this->returnType
     */
    public function hasReturnType(): bool
    {
        return $this->returnType !== null;
    }
}
