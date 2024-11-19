<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 这个类是用来序列化一些特殊对象的
 * 在传输一些对象给golang jobs时需要序列化，然后反序列化成对象，方便使用
 * 主要是在orm proxy中需要特殊处理，其余的对象使用默认的就行了
 * 后面如果也有需要特殊序列化处理的对象也在这里统一处理
 */

namespace App\Libraries\Core\Consumer;

use App\Libraries\Core\DB\BaseModel;

class SmartSerializer
{
    private const MODEL_CLASS_KEY = '__cycle_model';
    private const MODEL_ID_KEY = '__cycle_id';

    /**
     * 将任意数据序列化为字符串
     */
    public function serialize($data): string
    {
        $prepared = $this->prepareForSerialization($data);

        return serialize($prepared);
    }

    /**
     * 准备序列化数据
     */
    private function prepareForSerialization($data): mixed
    {
        if (is_object($data)) {
            return $this->prepareObject($data);
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->prepareForSerialization($value);
            }

            return $result;
        }

        return $data;
    }

    /**
     * 准备对象序列化
     */
    private function prepareObject(object $object): array|object
    {
        if ($object instanceof BaseModel) {
            $realClassName = get_parent_class($object);

            return [
                self::MODEL_CLASS_KEY => $realClassName,
                self::MODEL_ID_KEY => $object->getPrimaryKeys(),
            ];
        }

        return $object;
    }

    /**
     * 从序列化字符串中还原数据
     */
    public function unserialize(string $data): mixed
    {
        $decoded = unserialize($data);

        return $this->restoreFromSerialization($decoded);
    }

    /**
     * 从序列化数据中还原
     */
    private function restoreFromSerialization($data): mixed
    {
        if (is_array($data)) {
            if (isset($data[self::MODEL_CLASS_KEY]) && isset($data[self::MODEL_ID_KEY])) {
                $className = $data[self::MODEL_CLASS_KEY];

                return $className::findByPk($data[self::MODEL_ID_KEY]);
            }

            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->restoreFromSerialization($value);
            }

            return $result;
        }

        return $data;
    }
}
