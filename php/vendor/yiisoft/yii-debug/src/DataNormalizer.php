<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

use __PHP_Incomplete_Class;
use Closure;
use Yiisoft\VarDumper\ClosureExporter;

use function array_key_exists;
use function count;
use function gettype;
use function is_array;
use function is_object;
use function is_resource;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strpos;
use function substr;

/**
 * @internal
 *
 * Normalize data to array, scalar and null values only.
 */
final class DataNormalizer
{
    private readonly array $excludedClasses;
    private static ?ClosureExporter $closureExporter = null;

    public function __construct(array $excludedClasses = [])
    {
        $this->excludedClasses = array_flip($excludedClasses);
    }

    /**
     * @psalm-param positive-int|null $depth
     * @psalm-return list{mixed, array}
     */
    public function prepareDataAndObjectsMap(array $value, ?int $depth = null): array
    {
        $objectsData = $this->makeObjectsData($value);

        $objectsMap = array_map(
            fn (object $object): mixed => $this->normalize(
                $object,
                $depth === null ? null : ($depth + 1),
                $objectsData,
            ),
            $objectsData,
        );

        $data = $this->normalize($value, $depth, $objectsData);

        return [$data, $objectsMap];
    }

    /**
     * @psalm-param positive-int|null $depth
     */
    public function prepareData(array $value, ?int $depth = null): mixed
    {
        return $this->normalize($value, $depth);
    }

    /**
     * @psalm-param positive-int|null $depth
     * @psalm-param array<string, object> $objectsData
     */
    private function normalize(mixed $value, ?int $depth, array $objectsData = [], int $level = 0): mixed
    {
        return match (gettype($value)) {
            'array' => $this->normalizeArray($value, $depth, $objectsData, $level),
            'object' => $this->normalizeObject($value, $depth, $objectsData, $level),
            'resource', 'resource (closed)' => $this->normalizeResource($value),
            default => $value,
        };
    }

    /**
     * @psalm-param positive-int|null $depth
     * @psalm-param array<string, object> $objectsData
     */
    private function normalizeArray(array $array, ?int $depth, array $objectsData, int $level): string|array
    {
        if ($depth !== null && $depth <= $level) {
            $valuesCount = count($array);
            if ($valuesCount === 0) {
                return [];
            }
            return sprintf('array (%d %s) [...]', $valuesCount, $valuesCount === 1 ? 'item' : 'items');
        }

        $result = [];
        foreach ($array as $key => $value) {
            $keyDisplay = str_replace("\0", '::', trim((string) $key));
            $result[$keyDisplay] = $this->normalize($value, $depth, $objectsData, $level + 1);
        }
        return $result;
    }

    /**
     * @param positive-int|null $depth
     * @psalm-param array<string, object> $objectsData
     */
    private function normalizeObject(object $object, ?int $depth, array $objectsData, int $level): string|array
    {
        if ($object instanceof Closure) {
            return $this->normalizeClosure($object);
        }

        $objectId = $this->makeObjectId($object);

        if ($level > 0 && array_key_exists($objectId, $objectsData)) {
            return 'object@' . $objectId;
        }

        if (
            ($depth !== null && $depth <= $level)
            || array_key_exists($object::class, $this->excludedClasses)
            || !array_key_exists($objectId, $objectsData)
        ) {
            return $objectId . ' (...)';
        }

        $properties = $this->getObjectProperties($object);
        if (empty($properties)) {
            return '{stateless object}';
        }

        $result = [];
        foreach ($properties as $key => $value) {
            $keyDisplay = $this->normalizeProperty((string) $key);
            $result[$keyDisplay] = $this->normalize($value, $depth, $objectsData, $level + 1);
        }
        return $result;
    }

    private function normalizeClosure(Closure $closure): string
    {
        return (self::$closureExporter ??= new ClosureExporter())->export($closure);
    }

    private function normalizeResource(mixed $resource): array|string
    {
        if (!is_resource($resource)) {
            return '{closed resource}';
        }

        $type = get_resource_type($resource);
        if ($type === 'stream') {
            return stream_get_meta_data($resource);
        }
        if (!empty($type)) {
            return sprintf('{%s resource}', $type);
        }

        return '{resource}';
    }

    /**
     * @psalm-return array<string, object>
     */
    private function makeObjectsData(mixed $value): array
    {
        $objectsData = [];
        $this->internalMakeObjectsData($value, $objectsData);
        return $objectsData;
    }

    /**
     * @psalm-param array<string, object> $objectsData
     */
    private function internalMakeObjectsData(mixed $value, array &$objectsData): void
    {
        if (is_object($value)) {
            if ($value instanceof Closure || array_key_exists($value::class, $this->excludedClasses)) {
                return;
            }
            $objectId = $this->makeObjectId($value);
            if (array_key_exists($objectId, $objectsData)) {
                return;
            }
            $objectsData[$objectId] = $value;
        }

        if (is_object($value)) {
            foreach ($this->getObjectProperties($value) as $propertyValue) {
                $this->internalMakeObjectsData($propertyValue, $objectsData);
            }
            return;
        }

        if (is_array($value)) {
            foreach ($value as $arrayItem) {
                $this->internalMakeObjectsData($arrayItem, $objectsData);
            }
        }
    }

    private function makeObjectId(object $object): string
    {
        if (str_contains($object::class, '@anonymous')) {
            return 'class@anonymous#' . spl_object_id($object);
        }
        return $object::class . '#' . spl_object_id($object);
    }

    private function getObjectProperties(object $object): array
    {
        if (__PHP_Incomplete_Class::class !== $object::class && method_exists($object, '__debugInfo')) {
            $object = $object->__debugInfo();
        }
        return (array) $object;
    }

    private function normalizeProperty(string $property): string
    {
        $property = str_replace("\0", '::', trim($property));

        if (str_starts_with($property, '*::')) {
            return 'protected $' . substr($property, 3);
        }

        if (($pos = strpos($property, '::')) !== false) {
            return 'private $' . substr($property, $pos + 2);
        }

        return 'public $' . $property;
    }
}
