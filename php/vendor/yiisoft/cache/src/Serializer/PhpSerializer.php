<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Serializer;

use function serialize;
use function unserialize;

final class PhpSerializer implements SerializerInterface
{
    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function unserialize(string $data): mixed
    {
        return unserialize($data);
    }
}
