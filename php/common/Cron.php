<?php

namespace Common;

class Cron
{
    private string $name;
    private string $spec;
    private string $handler;
    private array $data;

    public static function name(string $name)
    {
        $obj = new self();
        $obj->name = $name;

        return $obj;
    }

    public function spec(string $spec)
    {
        $this->spec = $spec;

        return $this;
    }

    public function action(string $className, array $data)
    {
        $this->handler = $className;
        $this->data = $data;

        return $this;
    }

    public function register()
    {
        Yii::getRpcClient()->call('cron.Add', [
            'name' => $this->name,
            'spec' => $this->spec,
            'handler' => $this->handler,
            'data' => serialize($this->data),
        ]);
    }
}
