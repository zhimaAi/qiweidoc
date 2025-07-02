<?php

declare(strict_types=1);

namespace Yiisoft\VarDumper;

use __PHP_Incomplete_Class;
use Closure;
use DateTimeInterface;
use Exception;
use IteratorAggregate;
use JsonException;
use JsonSerializable;
use ReflectionException;
use ReflectionObject;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\VarDumper\Handler\EchoHandler;

use function array_keys;
use function gettype;
use function method_exists;
use function next;
use function spl_object_id;
use function str_repeat;
use function strtr;
use function trim;
use function var_export;

/**
 * VarDumper provides enhanced versions of the PHP functions {@see var_dump()} and {@see var_export()}.
 * It can:
 *
 * - Correctly identify the recursively referenced objects in a complex object structure.
 * - Recursively control depth to avoid indefinite recursive display of some peculiar variables.
 * - Export closures and objects.
 * - Highlight output.
 * - Format output.
 */
final class VarDumper
{
    public const VAR_TYPE_PROPERTY = '$__type__$';
    public const OBJECT_ID_PROPERTY = '$__id__$';
    public const OBJECT_CLASS_PROPERTY = '$__class__$';
    public const DEPTH_LIMIT_EXCEEDED_PROPERTY = '$__depth_limit_exceeded__$';

    public const VAR_TYPE_ARRAY = 'array';
    public const VAR_TYPE_OBJECT = 'object';
    public const VAR_TYPE_RESOURCE = 'resource';
    private static ?HandlerInterface $defaultHandler = null;

    /**
     * @var string[] Variables using in closure scope.
     */
    private array $useVarInClosures = [];
    private bool $serializeObjects = true;
    /**
     * @var string Offset to use to indicate nesting level.
     */
    private string $offset = '    ';
    private static ?ClosureExporter $closureExporter = null;

    /**
     * @param mixed $variable Variable to dump.
     */
    private function __construct(private mixed $variable)
    {
    }

    public static function setDefaultHandler(HandlerInterface $handler): void
    {
        self::$defaultHandler = $handler;
    }

    public static function getDefaultHandler(): HandlerInterface
    {
        return self::$defaultHandler ??= new EchoHandler();
    }

    /**
     * @param mixed $variable Variable to dump.
     *
     * @return static An instance containing variable to dump.
     */
    public static function create(mixed $variable): self
    {
        return new self($variable);
    }

    /**
     * Prints a variable.
     *
     * This method achieves the similar functionality as {@see var_dump()} and {@see print_r()}
     * but is more robust when handling complex objects.
     *
     * @param mixed $variable Variable to be dumped.
     * @param int $depth Maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight Whether the result should be syntax-highlighted.
     *
     * @throws ReflectionException
     */
    public static function dump(mixed $variable, int $depth = 10, bool $highlight = true): void
    {
        self::getDefaultHandler()->handle($variable, $depth, $highlight);
    }

    /**
     * Sets offset string to use to indicate nesting level.
     *
     * @param string $offset The offset string.
     *
     * @return static New instance with a given offset.
     */
    public function withOffset(string $offset): self
    {
        $new = clone $this;
        $new->offset = $offset;

        return $new;
    }

    /**
     * Dumps a variable in terms of a string.
     *
     * This method achieves the similar functionality as {@see var_dump()} and {@see print_r()}
     * but is more robust when handling complex objects.
     *
     * @param int $depth Maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param bool $highlight Whether the result should be syntax-highlighted.
     *
     * @throws ReflectionException
     *
     * @return string The string representation of the variable.
     */
    public function asString(int $depth = 10): string
    {
        return $this->dumpInternal($this->variable, true, $depth, 0);
    }

    /**
     * Dumps a variable as a JSON string.
     *
     * This method provides similar functionality to the {@see json_encode()}
     * but is more robust when handling complex objects.
     *
     * @param bool $format Use whitespaces in returned data to format it
     * @param int $depth Maximum depth that the dumper should go into the variable. Defaults to 10.
     *
     * @throws JsonException
     *
     * @return string The json representation of the variable.
     */
    public function asJson(bool $format = true, int $depth = 10): string
    {
        /** @var mixed $output */
        $output = $this->asPrimitives($depth);

        if ($format) {
            return json_encode($output, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        }

        return json_encode($output, JSON_THROW_ON_ERROR);
    }

    /**
     * Dumps the variable as PHP primitives in JSON decoded style.
     *
     * @param int $depth Maximum depth that the dumper should go into the variable. Defaults to 10.
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    public function asPrimitives(int $depth = 10): mixed
    {
        return $this->exportPrimitives($this->variable, $depth, 0);
    }

    /**
     * Exports a variable as a string containing PHP code.
     *
     * The string is a valid PHP expression that can be evaluated by PHP parser
     * and the evaluation result will give back the variable value.
     *
     * This method is similar to {@see var_export()}. The main difference is that
     * it generates more compact string representation using short array syntax.
     *
     * It also handles closures with {@see ClosureExporter} and objects
     * by using the PHP functions {@see serialize()} and {@see unserialize()}.
     *
     * @param bool $format Whatever to format code.
     * @param string[] $useVariables Array of variables used in `use` statement (['$params', '$config'])
     * @param bool $serializeObjects If it is true all objects will be serialized except objects with closure(s). If it
     * is false only objects of internal classes will be serialized.
     *
     * @throws ReflectionException
     *
     * @return string A PHP code representation of the variable.
     */
    public function export(bool $format = true, array $useVariables = [], bool $serializeObjects = true): string
    {
        $this->useVarInClosures = $useVariables;
        $this->serializeObjects = $serializeObjects;
        return $this->exportInternal($this->variable, $format, 0);
    }

    /**
     * @param mixed $var Variable to be dumped.
     * @param bool $format Whatever to format code.
     * @param int $depth Maximum depth.
     * @param int $level Current depth.
     *
     * @throws ReflectionException
     *
     * @return string
     */
    private function dumpInternal(mixed $var, bool $format, int $depth, int $level): string
    {
        switch (gettype($var)) {
            case 'resource':
            case 'resource (closed)':
                return '{resource}';
            case 'NULL':
                return 'null';
            case 'array':
                if ($depth <= $level) {
                    return '[...]';
                }

                if (empty($var)) {
                    return '[]';
                }

                $output = '';
                $keys = array_keys($var);
                $spaces = str_repeat($this->offset, $level);
                $output .= '[';

                foreach ($keys as $name) {
                    if ($format) {
                        $output .= "\n" . $spaces . $this->offset;
                    }
                    $output .= $this->exportVariable($name);
                    $output .= ' => ';
                    $output .= $this->dumpInternal($var[$name], $format, $depth, $level + 1);
                }

                return $format
                    ? $output . "\n" . $spaces . ']'
                    : $output . ']';
            case 'object':
                if ($var instanceof Closure) {
                    return $this->exportClosure($var);
                }
                if ($var instanceof DateTimeInterface) {
                    return $this->exportDateTime($var);
                }

                if ($depth <= $level) {
                    return $this->getObjectDescription($var) . ' (...)';
                }

                $spaces = str_repeat($this->offset, $level);
                $output = $this->getObjectDescription($var) . "\n" . $spaces . '(';
                $objectProperties = $this->getObjectProperties($var);

                /** @psalm-var mixed $value */
                foreach ($objectProperties as $name => $value) {
                    $propertyName = strtr(trim((string) $name), "\0", '::');
                    $output .= "\n" . $spaces . $this->offset . '[' . $propertyName . '] => ';
                    $output .= $this->dumpInternal($value, $format, $depth, $level + 1);
                }
                return $output . "\n" . $spaces . ')';
            default:
                return $this->exportVariable($var);
        }
    }

    /**
     * @param mixed $variable Variable to be exported.
     * @param bool $format Whatever to format code.
     * @param int $level Current depth.
     *
     * @throws ReflectionException
     *
     * @return string
     */
    private function exportInternal(mixed $variable, bool $format, int $level): string
    {
        $spaces = str_repeat($this->offset, $level);
        switch (gettype($variable)) {
            case 'NULL':
                return 'null';
            case 'array':
                if (empty($variable)) {
                    return '[]';
                }

                $keys = array_keys($variable);
                $outputKeys = $keys !== array_keys($keys);
                $output = '[';

                foreach ($keys as $key) {
                    if ($format) {
                        $output .= "\n" . $spaces . $this->offset;
                    }
                    if ($outputKeys) {
                        $output .= $this->exportVariable($key);
                        $output .= ' => ';
                    }
                    $output .= $this->exportInternal($variable[$key], $format, $level + 1);
                    if ($format || next($keys) !== false) {
                        $output .= ',';
                    }
                }

                return $format
                    ? $output . "\n" . $spaces . ']'
                    : $output . ']';
            case 'object':
                if ($variable instanceof Closure) {
                    return $this->exportClosure($variable, $level);
                }
                if ($variable instanceof DateTimeInterface) {
                    return $this->exportDateTime($variable);
                }


                $reflectionObject = new ReflectionObject($variable);
                try {
                    if ($this->serializeObjects || $reflectionObject->isInternal() || $reflectionObject->isAnonymous()) {
                        return "unserialize({$this->exportVariable(serialize($variable))})";
                    }

                    return $this->exportObject($variable, $reflectionObject, $format, $level);
                } catch (Exception) {
                    // Serialize may fail, for example: if object contains a `\Closure` instance so we use a fallback.
                    if ($this->serializeObjects && !$reflectionObject->isInternal() && !$reflectionObject->isAnonymous()) {
                        try {
                            return $this->exportObject($variable, $reflectionObject, $format, $level);
                        } catch (Exception) {
                            return $this->exportObjectFallback($variable, $format, $level);
                        }
                    }

                    return $this->exportObjectFallback($variable, $format, $level);
                }
            default:
                return $this->exportVariable($variable);
        }
    }

    /**
     * @throws ReflectionException
     *
     * @return mixed
     * @psalm-param mixed $var
     */
    private function exportPrimitives(mixed $var, int $depth, int $level): mixed
    {
        switch (gettype($var)) {
            case 'resource':
            case 'resource (closed)':
                /** @var resource $var */
                $id = get_resource_id($var);
                $type = get_resource_type($var);

                return [
                    self::VAR_TYPE_PROPERTY => self::VAR_TYPE_RESOURCE,
                    'id' => $id,
                    'type' => $type,
                    'closed' => !is_resource($var),
                ];
            case 'array':
                if ($depth <= $level) {
                    return [
                        self::VAR_TYPE_PROPERTY => self::VAR_TYPE_ARRAY,
                        self::DEPTH_LIMIT_EXCEEDED_PROPERTY => true,
                    ];
                }

                /** @psalm-suppress MissingClosureReturnType */
                return array_map(fn ($value) => $this->exportPrimitives($value, $depth, $level + 1), $var);
            case 'object':
                if ($var instanceof Closure) {
                    return $this->exportClosure($var);
                }

                $objectClass = $var::class;
                $objectId = $this->getObjectId($var);
                if ($depth <= $level) {
                    return [
                        self::VAR_TYPE_PROPERTY => self::VAR_TYPE_OBJECT,
                        self::OBJECT_ID_PROPERTY => $objectId,
                        self::OBJECT_CLASS_PROPERTY => $objectClass,
                        self::DEPTH_LIMIT_EXCEEDED_PROPERTY => true,
                    ];
                }

                $objectProperties = $this->getObjectProperties($var);

                $output = [
                    self::OBJECT_ID_PROPERTY => $objectId,
                    self::OBJECT_CLASS_PROPERTY => $objectClass,
                ];
                /**
                 * @psalm-var mixed $value
                 * @psalm-var string $name
                 */
                foreach ($objectProperties as $name => $value) {
                    $propertyName = $this->getPropertyName($name);
                    /** @psalm-suppress MixedAssignment */
                    $output[$propertyName] = $this->exportPrimitives($value, $depth, $level + 1);
                }
                return $output;
            default:
                return $var;
        }
    }

    private function getPropertyName(string|int $property): string
    {
        if (is_int($property)) {
            return (string) $property;
        }
        $property = str_replace("\0", '::', trim($property));

        if (str_starts_with($property, '*::')) {
            return substr($property, 3);
        }

        if (($pos = strpos($property, '::')) !== false) {
            return substr($property, $pos + 2);
        }

        return $property;
    }

    /**
     * @throws ReflectionException
     *
     * @return string
     */
    private function exportObjectFallback(object $variable, bool $format, int $level): string
    {
        if ($variable instanceof ArrayableInterface) {
            return $this->exportInternal($variable->toArray(), $format, $level);
        }

        if ($variable instanceof JsonSerializable) {
            return $this->exportInternal($variable->jsonSerialize(), $format, $level);
        }

        if ($variable instanceof IteratorAggregate) {
            return $this->exportInternal(iterator_to_array($variable), $format, $level);
        }

        /** @psalm-suppress RedundantCondition */
        if ('__PHP_Incomplete_Class' !== $variable::class && method_exists($variable, '__toString')) {
            return $this->exportVariable($variable->__toString());
        }

        return $this->exportVariable(self::create($variable)->asString());
    }

    private function exportObject(object $variable, ReflectionObject $reflectionObject, bool $format, int $level): string
    {
        $spaces = str_repeat($this->offset, $level);
        $objectProperties = $this->getObjectProperties($variable);
        $class = $variable::class;
        $use = $this->useVarInClosures === [] ? '' : ' use (' . implode(', ', $this->useVarInClosures) . ')';
        $lines = ['(static function ()' . $use . ' {',];
        if ($reflectionObject->getConstructor() === null) {
            $lines = [
                ...$lines,
                $this->offset . '$object = new ' . $class . '();',
                $this->offset . '(function ()' . $use . ' {',
            ];
        } else {
            $lines = [
                ...$lines,
                $this->offset . '$class = new \ReflectionClass(\'' . $class . '\');',
                $this->offset . '$object = $class->newInstanceWithoutConstructor();',
                $this->offset . '(function ()' . $use . ' {',
            ];
        }
        $endLines = [
            $this->offset . '})->bindTo($object, \'' . $class . '\')();',
            '',
            $this->offset . 'return $object;',
            '})()',
        ];

        /**
         * @psalm-var mixed $value
         * @psalm-var string $name
         */
        foreach ($objectProperties as $name => $value) {
            $propertyName = $this->getPropertyName($name);
            $lines[] = $this->offset . $this->offset . '$this->' . $propertyName . ' = ' .
                $this->exportInternal($value, $format, $level + 2) . ';';
        }

        return implode("\n" . ($format ? $spaces : ''), array_merge($lines, $endLines));
    }

    /**
     * Exports a {@see \Closure} instance.
     *
     * @param Closure $closure Closure instance.
     *
     * @throws ReflectionException
     *
     * @return string
     */
    private function exportClosure(Closure $closure, int $level = 0): string
    {
        if (self::$closureExporter === null) {
            self::$closureExporter = new ClosureExporter();
        }

        return self::$closureExporter->export($closure, $level);
    }

    /**
     * @return string
     */
    private function exportVariable(mixed $variable): string
    {
        return var_export($variable, true);
    }

    private function getObjectDescription(object $object): string
    {
        return $object::class . '#' . $this->getObjectId($object);
    }

    private function getObjectId(object $object): string
    {
        return (string) spl_object_id($object);
    }

    private function getObjectProperties(object $var): array
    {
        if (!$var instanceof __PHP_Incomplete_Class && method_exists($var, '__debugInfo')) {
            /** @var array $var */
            $var = $var->__debugInfo();
        }

        return (array) $var;
    }

    private function exportDateTime(DateTimeInterface $variable): string
    {
        return sprintf(
            "new \%s('%s', new \DateTimeZone('%s'))",
            $variable::class,
            $variable->format(DateTimeInterface::RFC3339_EXTENDED),
            $variable->getTimezone()->getName()
        );
    }
}
