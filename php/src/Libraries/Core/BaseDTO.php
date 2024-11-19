<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Libraries\Core;

use App\Libraries\Core\Exception\ValidationException;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\String\UnicodeString;
use Yiisoft\Validator\Validator;

abstract class BaseDTO
{
    public function __construct(?array $data = [], $autoValid = true)
    {
        $camelCaseData = $this->convertArrayKeys(is_null($data) ? [] : $data);
        if ($autoValid) {
            $this->validate($camelCaseData);
        }

        $this->loadData($camelCaseData);
    }

    protected function loadData(array $data): void
    {
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (array_key_exists($propertyName, $data)) {
                $this->$propertyName = $data[$propertyName];
            }
        }
    }

    public function validate($data)
    {
        $result = (new Validator())->validate($data, $this);
        if (! $result->isValid()) {
            throw new ValidationException($result, "参数验证错误：" . $result->getErrorMessages()[0]);
        }
    }

    public function convertArrayKeys(array $array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            // 转换当前key为驼峰格式
            $newKey = $this->convertKeyToCamelCase($key);

            // 如果值是数组，递归处理
            if (is_array($value)) {
                $result[$newKey] = $this->convertArrayKeys($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    public function convertKeyToCamelCase(string $key): string
    {
        // 先转换成小写
        $key = strtolower($key);

        // 将下划线后的字母转换成大写
        $key = preg_replace_callback('/_([a-z])/', function ($matches) {
            return ucfirst($matches[1]);
        }, $key);

        return $key;
    }

    /**
     * 将 DTO 转换为数组
     *
     * @param string $case 输出格式：'camel', 'snake', 'kebab', 'pascal'
     * @return array
     */
    public function toArray(string $case = 'camel'): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $result = [];
        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $this->$name;

            // 使用 Symfony String 进行格式转换
            $string = new UnicodeString($name);
            $key = match($case) {
                'snake' => $string->snake()->toString(),
                'kebab' => $string->snake()->replace('_', '-')->toString(),
                'pascal' => $string->camel()->title()->toString(),
                default => $name,
            };

            $result[$key] = $value;
        }

        return $result;
    }
}
