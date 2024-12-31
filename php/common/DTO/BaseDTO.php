<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\DTO;

use Common\Exceptions\ValidationException;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\String\UnicodeString;
use Throwable;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Validator;

abstract class BaseDTO implements RulesProviderInterface
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

    /**
     * @throws Throwable
     */
    public function validate($data)
    {
        $result = (new Validator())->validate($data, $this);
        if (!$result->isValid()) {
            throw new ValidationException($result);
        }
    }

    // public function get(string $key)
    // {
    //     $data = $this->toArray();
    //     $keys = explode('.', $key); // 按 . 分割键
    //
    //     foreach ($keys as $keyPart) {
    //         if (is_numeric($keyPart)) {
    //             // 如果是数字，转换为整型以支持访问数组的索引
    //             $keyPart = (int) $keyPart;
    //         }
    //         if (isset($data[$keyPart])) {
    //             $data = $data[$keyPart];
    //         } else {
    //             return null; // 如果键不存在，返回 null
    //         }
    //     }
    //
    //     return $data; // 返回最终的值
    // }

    // /**
    //  * @throws Throwable
    //  */
    // public function __get(string $key)
    // {
    //     throw new Exception("禁止使用魔术方法");
    // }

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
    public function toArray(string $case = 'snake'): array
    {
        return $this->convertToArrayWithCase($this, $case);
    }

    /**
     * 递归转换数组中的键名格式
     *
     * @param mixed $data 要转换的数据
     * @param string $case 目标格式
     * @return mixed
     */
    protected function convertToArrayWithCase(mixed $data, string $case): mixed
    {
        if ($data instanceof BaseDTO) {
            $reflection = new ReflectionClass($data);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            $result = [];
            foreach ($properties as $property) {
                $name = $property->getName();
                $value = $data->$name;

                // 转换键名格式
                $string = new UnicodeString($name);
                $key = match($case) {
                    'snake' => $string->snake()->toString(),
                    'kebab' => $string->snake()->replace('_', '-')->toString(),
                    'pascal' => $string->camel()->title()->toString(),
                    default => $name,
                };

                // 递归处理值
                $result[$key] = $this->convertToArrayWithCase($value, $case);
            }

            return $result;
        }

        // 处理数组
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                // 转换键名格式
                $string = new UnicodeString($key);
                $newKey = match($case) {
                    'snake' => $string->snake()->toString(),
                    'kebab' => $string->snake()->replace('_', '-')->toString(),
                    'pascal' => $string->camel()->title()->toString(),
                    default => $key,
                };

                // 递归处理值
                $result[$newKey] = $this->convertToArrayWithCase($value, $case);
            }

            return $result;
        }

        // 对于其他类型的值，直接返回
        return $data;
    }
}
