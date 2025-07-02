<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

use Yiisoft\Strings\StringHelper;

use function array_key_exists;
use function is_array;
use function is_string;
use function strlen;

/**
 * Holds data to hydrate an object from and a map to use when populating an object.
 *
 * @psalm-type MapType=array<string,string|list<string>>
 */
final class ArrayData implements DataInterface
{
    /**
     * @param array $data Data to hydrate object from.
     * @param array $map Object property names mapped to keys in the data array that hydrator will use when hydrating
     * an object.
     * @param bool $strict Whether to hydrate properties from the map only.
     *
     * @psalm-param MapType $map
     */
    public function __construct(
        private array $data = [],
        private array $map = [],
        private bool $strict = false,
    ) {
    }

    public function getValue(string $name): Result
    {
        if ($this->strict && !array_key_exists($name, $this->map)) {
            return Result::fail();
        }

        return $this->getValueByPath($this->data, $this->map[$name] ?? $name);
    }

    /**
     * Get value from an array given a path.
     *
     * @param string|string[] $path Path to the value.
     *
     * @see StringHelper::parsePath()
     */
    private function getValueByPath(array $data, string|array $path): Result
    {
        if (is_string($path)) {
            $path = StringHelper::parsePath($path);
        }

        $result = Result::success($data);
        foreach ($path as $pathKey) {
            $currentValue = $result->getValue();
            if (!is_array($currentValue)) {
                return Result::fail();
            }
            $result = $this->getValueByKey($currentValue, $pathKey);
            if (!$result->isResolved()) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * Get value from an array given a key.
     *
     * @param array $data Array to get value from.
     * @param string $pathKey Key to get value for.
     *
     * @return Result The result object.
     */
    private function getValueByKey(array $data, string $pathKey): Result
    {
        $found = false;
        $result = null;
        foreach ($data as $dataKey => $dataValue) {
            $dataKey = (string) $dataKey;

            if ($dataKey === $pathKey) {
                $found = true;
                $result = (is_array($dataValue) && is_array($result))
                    ? array_merge($result, $dataValue)
                    : $dataValue;
                continue;
            }

            $pathKeyWithDot = $pathKey . '.';
            if (str_starts_with($dataKey, $pathKeyWithDot)) {
                $found = true;
                $value = [
                    substr($dataKey, strlen($pathKeyWithDot)) => $dataValue,
                ];
                $result = is_array($result)
                    ? array_merge($result, $value)
                    : $value;
            }
        }

        return $found ? Result::success($result) : Result::fail();
    }
}
