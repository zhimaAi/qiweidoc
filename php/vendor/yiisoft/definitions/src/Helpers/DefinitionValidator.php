<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Helpers;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\InvalidConfigException;

use function count;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Definition validator checks if definition is valid.
 */
final class DefinitionValidator
{
    /**
     * Validates that definition is valid. Throws exception otherwise.
     *
     * @param mixed $definition Definition to validate.
     *
     * @throws InvalidConfigException If definition is not valid.
     * @throws ReflectionException
     */
    public static function validate(mixed $definition, ?string $id = null): void
    {
        // Reference or ready object
        if (is_object($definition) && self::isValidObject($definition)) {
            return;
        }

        // Class
        if (is_string($definition)) {
            self::validateString($definition);
            return;
        }

        // Callable definition
        if (is_callable($definition, true)) {
            return;
        }

        // Array definition
        if (is_array($definition)) {
            self::validateArrayDefinition($definition, $id);
            return;
        }

        throw new InvalidConfigException(
            'Invalid definition: '
            . ($definition === '' ? 'empty string.' : var_export($definition, true)),
        );
    }

    /**
     * Validates that array definition is valid. Throws exception otherwise.
     *
     * @param array $definition Array definition to validate.
     *
     * @throws InvalidConfigException If definition is not valid.
     * @throws ReflectionException
     */
    public static function validateArrayDefinition(array $definition, ?string $id = null): void
    {
        /** @var class-string $className */
        $className = $definition[ArrayDefinition::CLASS_NAME] ?? $id ?? throw new InvalidConfigException(
            'Invalid definition: no class name specified.',
        );
        self::validateString($className);
        $classReflection = new ReflectionClass($className);
        $classPublicMethods = [];
        foreach ($classReflection->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isPublic() && !self::isMagicMethod($reflectionMethod->getName())) {
                $classPublicMethods[] = $reflectionMethod->getName();
            }
        }
        $classPublicProperties = [];
        foreach ($classReflection->getProperties() as $reflectionProperty) {
            if (self::isPublicWritableProperty($reflectionProperty)) {
                $classPublicProperties[] = $reflectionProperty->getName();
            }
        }

        foreach ($definition as $key => $value) {
            if (!is_string($key)) {
                throw ExceptionHelper::invalidArrayDefinitionKey($key);
            }

            // Class
            if ($key === ArrayDefinition::CLASS_NAME) {
                continue;
            }

            // Constructor arguments
            if ($key === ArrayDefinition::CONSTRUCTOR) {
                self::validateConstructor($value);
                continue;
            }

            // Methods and properties
            if ((count($methodArray = explode('()', $key)) === 2) && !empty($methodArray[0])) {
                self::validateMethod($methodArray[0], $classReflection, $classPublicMethods, $className, $value);
                continue;
            }
            if (str_starts_with($key, '$')) {
                self::validateProperty($key, $classReflection, $classPublicProperties, $className);
                continue;
            }

            $possibleOptionsMessage = self::generatePossibleMessage(
                $key,
                $classPublicMethods,
                $classPublicProperties,
                $classReflection,
                $className,
            );

            throw new InvalidConfigException(
                "Invalid definition: key \"$key\" is not allowed. $possibleOptionsMessage",
            );
        }
    }

    /**
     * Deny `DefinitionInterface`, exclude `ReferenceInterface`
     */
    private static function isValidObject(object $value): bool
    {
        return !($value instanceof DefinitionInterface) || $value instanceof ReferenceInterface;
    }

    private static function generatePossibleMessage(
        string $key,
        array $classPublicMethods,
        array $classPublicProperties,
        ReflectionClass $classReflection,
        string $className,
    ): string {
        $parsedKey = trim(
            strtr($key, [
                '()' => '',
                '$' => '',
            ]),
        );
        if (in_array($parsedKey, $classPublicMethods, true)) {
            return sprintf(
                'Did you mean "%s"?',
                $parsedKey . '()',
            );
        }
        if (in_array($parsedKey, $classPublicProperties, true)) {
            return sprintf(
                'Did you mean "%s"?',
                '$' . $parsedKey,
            );
        }
        if ($classReflection->hasMethod($parsedKey)) {
            return sprintf(
                'Method "%s" must be public to be able to be called.',
                $className . '::' . $parsedKey . '()',
            );
        }
        if ($classReflection->hasProperty($parsedKey)) {
            return sprintf(
                'Property "%s" must be public to be able to be called.',
                $className . '::$' . $parsedKey,
            );
        }

        return 'The key may be a call of a method or a setting of a property.';
    }

    /**
     * @param string[] $classPublicMethods
     *
     * @throws InvalidConfigException
     */
    private static function validateMethod(
        string $methodName,
        ReflectionClass $classReflection,
        array $classPublicMethods,
        string $className,
        mixed $value,
    ): void {
        if (!$classReflection->hasMethod($methodName)) {
            if ($classReflection->hasMethod('__call') || $classReflection->hasMethod('__callStatic')) {
                /**
                 * Magic method may intercept the call, but reflection does not know about it.
                 */
                return;
            }
            $possiblePropertiesMessage = $classPublicMethods === []
                ? 'No public methods available to call.'
                : sprintf(
                    'Possible methods to call: %s',
                    '"' . implode('", "', $classPublicMethods) . '"',
                );
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: class "%s" does not have the public method with name "%s". ' . $possiblePropertiesMessage,
                    $className,
                    $methodName,
                ),
            );
        }
        if (!in_array($methodName, $classPublicMethods, true)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: method "%s" must be public.',
                    $className . '::' . $methodName . '()',
                ),
            );
        }
        if (!is_array($value)) {
            throw ExceptionHelper::incorrectArrayDefinitionMethodArguments($methodName . '()', $value);
        }
    }

    /**
     * @param string[] $classPublicProperties
     *
     * @throws InvalidConfigException
     */
    private static function validateProperty(
        string $key,
        ReflectionClass $classReflection,
        array $classPublicProperties,
        string $className,
    ): void {
        $parsedKey = substr($key, 1);
        if (!$classReflection->hasProperty($parsedKey)) {
            if ($classReflection->hasMethod('__set')) {
                /**
                 * Magic method may intercept the call, but reflection does not know about it.
                 */
                return;
            }
            if ($classPublicProperties === []) {
                $message = sprintf(
                    'Invalid definition: class "%s" does not have any public properties.',
                    $className,
                );
            } else {
                $message = sprintf(
                    'Invalid definition: class "%s" does not have the public property with name "%s". Possible properties to set: %s.',
                    $className,
                    $parsedKey,
                    '"' . implode('", "', $classPublicProperties) . '"',
                );
            }
            throw new InvalidConfigException($message);
        } elseif (!in_array($parsedKey, $classPublicProperties, true)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: property "%s" must be public and writable.',
                    $className . '::' . $key,
                ),
            );
        }
    }

    /**
     * @throws InvalidConfigException
     */
    private static function validateConstructor(mixed $value): void
    {
        if (!is_array($value)) {
            throw ExceptionHelper::incorrectArrayDefinitionConstructorArguments($value);
        }

        foreach ($value as $argument) {
            if (is_object($argument) && !self::isValidObject($argument)) {
                throw new InvalidConfigException(
                    'Only references are allowed in constructor arguments, a definition object was provided: ' .
                    var_export($argument, true),
                );
            }
        }
    }

    private static function isMagicMethod(string $getName): bool
    {
        return in_array($getName, [
            '__construct',
            '__destruct',
            '__call',
            '__callStatic',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__sleep',
            '__wakeup',
            '__serialize',
            '__unserialize',
            '__toString',
            '__invoke',
            '__set_state',
            '__clone',
            '__debugInfo',
        ], true);
    }

    /**
     * @throws InvalidConfigException
     */
    private static function validateString(mixed $class): void
    {
        if (!is_string($class)) {
            throw new InvalidConfigException(
                sprintf(
                    'Invalid definition: class name must be a non-empty string, got %s.',
                    get_debug_type($class),
                ),
            );
        }
        if (trim($class) === '') {
            throw new InvalidConfigException('Invalid definition: class name must be a non-empty string.');
        }
    }

    private static function isPublicWritableProperty(ReflectionProperty $property): bool
    {
        if (!$property->isPublic()) {
            return false;
        }

        if ($property->isReadOnly()) {
            return false;
        }

        if (PHP_VERSION_ID < 80400) {
            return true;
        }

        $modifiers = $property->getModifiers();

        /**
         * @psalm-suppress UndefinedConstant, MixedOperand Needs for PHP 8.3 or lower
         */
        return ($modifiers & (ReflectionProperty::IS_PRIVATE_SET | ReflectionProperty::IS_PROTECTED_SET)) === 0;
    }
}
