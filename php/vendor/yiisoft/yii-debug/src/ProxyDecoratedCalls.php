<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

trait ProxyDecoratedCalls
{
    public function __set(string $name, mixed $value): void
    {
        $this->decorated->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->decorated->$name;
    }

    public function __call(string $name, array $arguments)
    {
        /**
         * @psalm-suppress MixedMethodCall
         */
        return $this->decorated->$name(...$arguments);
    }
}
