<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Common\DTO;

use Common\Exceptions\ValidationException;
use Exception;
use Throwable;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Validator;

abstract class CommonDTO extends \stdClass implements RulesProviderInterface
{
    public function __construct(?array $data, $autoValid = true)
    {
        // 把所有需要验证的字段设置为null
        $keys = array_filter(array_keys((array) $this->getRules()));
        foreach ($keys as $key) {
            $this->$key = null;
        }

        // 填充字段
        $data = $data ?: [];
        foreach ($data as $key => $value) {
            if (in_array($key, $keys)) {
                $this->$key = $value;
            }
        }

        // 验证
        if ($autoValid) {
            $this->validate();
        }
    }

    /**
     * @throws Throwable
     */
    public function validate()
    {
        $result = (new Validator())->validate($this);
        if (!$result->isValid()) {
            throw new ValidationException($result);
        }
    }

    public function get(string $key): mixed
    {
        if (isset($this->$key)) {
            return $this->$key;
        } else {
            return null;
        }
    }

    public function __get(string $key)
    {
        throw new Exception("禁止使用魔术方法");
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
