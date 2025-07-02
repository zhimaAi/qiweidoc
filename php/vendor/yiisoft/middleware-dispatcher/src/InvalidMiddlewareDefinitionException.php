<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Helpers\DefinitionValidator;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function array_slice;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

final class InvalidMiddlewareDefinitionException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    private string $definitionString;

    public function __construct(
        private mixed $definition
    ) {
        $this->definitionString = $this->convertDefinitionToString($definition);

        parent::__construct(
            'Parameter should be either PSR middleware class name or a callable. Got ' . $this->definitionString . '.'
        );
    }

    public function getName(): string
    {
        return 'Invalid middleware definition';
    }

    public function getSolution(): ?string
    {
        $solution = [
            <<<SOLUTION
            ## Got definition value

            `{$this->definitionString}`
            SOLUTION
        ];

        $suggestion = $this->generateSuggestion();
        if ($suggestion !== null) {
            $solution[] = '## Suggestion';
            $solution[] = $suggestion;
        }

        $solution[] = <<<SOLUTION
        ## Middleware definition examples

        PSR middleware class name:

        ```php
        Yiisoft\Session\SessionMiddleware::class
        ```

        PSR middleware array definition:

        ```php
        [
            'class' => MyMiddleware::class,
            '__construct()' => [
                'someVar' => 42,
            ],
        ]
        ```

        Closure that returns `ResponseInterface`:

        ```php
        static function (): ResponseInterface {
            return new Response(418);
        },
        ```

        Closure that returns `MiddlewareInterface`:

        ```php
        static function (): MiddlewareInterface {
            return new TestMiddleware();
        }
        ```

        Action in controller:

        ```php
        [App\Backend\UserController::class, 'index']
        ```

        ## Related links

        - [Array definition syntax](https://github.com/yiisoft/definitions#arraydefinition)
        - [Callable PHP documentation](https://www.php.net/manual/language.types.callable.php)
        SOLUTION;

        return implode("\n\n", $solution);
    }

    private function generateSuggestion(): ?string
    {
        if ($this->isControllerWithNonExistAction()) {
            return <<<SOLUTION
            Class `{$this->definition[0]}` exists, but does not contain method `{$this->definition[1]}()`.

            Try adding `{$this->definition[1]}()` action to `{$this->definition[0]}` controller:

            ```php
            public function {$this->definition[1]}(): ResponseInterface
            {
                // TODO: Implement your action
            }
            ```
            SOLUTION;
        }

        if ($this->isNotMiddlewareClassName()) {
            return sprintf(
                'Class `%s` exists, but does not implement `%s`.',
                $this->definition,
                MiddlewareInterface::class
            );
        }

        if ($this->isStringNotClassName()) {
            return sprintf(
                'Class `%s` not found. It may be needed to install a package with this middleware.',
                $this->definition
            );
        }

        if (is_array($this->definition)) {
            try {
                DefinitionValidator::validateArrayDefinition($this->definition);
            } catch (InvalidConfigException $e) {
                return <<<SOLUTION
                You may have an error in array definition. Array definition validation result:

                ```
                {$e->getMessage()}
                ```
                SOLUTION;
            }

            /** @psalm-suppress MixedArgument In valid array definition element "class" always is string */
            return sprintf(
                'Array definition is valid, class `%s` exists, but does not implement `%s`.',
                $this->definition['class'],
                MiddlewareInterface::class
            );
        }

        return null;
    }

    /**
     * @psalm-assert-if-true string $this->definition
     */
    private function isStringNotClassName(): bool
    {
        return is_string($this->definition)
            && !class_exists($this->definition);
    }

    /**
     * @psalm-assert-if-true class-string $this->definition
     */
    private function isNotMiddlewareClassName(): bool
    {
        return is_string($this->definition)
            && class_exists($this->definition);
    }

    /**
     * @psalm-assert-if-true array{0:class-string,1:string} $this->definition
     */
    private function isControllerWithNonExistAction(): bool
    {
        return is_array($this->definition)
            && array_keys($this->definition) === [0, 1]
            && is_string($this->definition[0])
            && class_exists($this->definition[0]);
    }

    private function convertDefinitionToString(mixed $middlewareDefinition): string
    {
        if (is_object($middlewareDefinition)) {
            return 'an instance of "' . $middlewareDefinition::class . '"';
        }

        if (is_string($middlewareDefinition)) {
            return '"' . $middlewareDefinition . '"';
        }

        if (is_array($middlewareDefinition)) {
            $items = [];
            /** @var mixed $value */
            foreach (array_slice($middlewareDefinition, 0, 2) as $key => $value) {
                $items[] = (is_string($key) ? '"' . $key . '" => ' : '') . $this->convertToString($value);
            }
            return '[' . implode(', ', $items) . (count($middlewareDefinition) > 2 ? ', ...' : '') . ']';
        }

        return $this->convertToString($middlewareDefinition);
    }

    private function convertToString(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . $value . '"';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return $value::class;
        }

        return gettype($value);
    }
}
