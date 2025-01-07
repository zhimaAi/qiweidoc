<?php

namespace Common;

class Broadcast
{
    private string $from;

    private string $event;

    public string $handler;

    public static function event(string $event)
    {
        $obj = new self();
        $obj->event = $event;

        return $obj;
    }

    public function from(string $from)
    {
        $this->from = $from;

        return $this;
    }

    public function action(string $className)
    {
        $this->handler = $className;

        return $this;
    }

    public function register()
    {
        Yii::getRpcClient()->call('broadcast.Register', [
            'from' => $this->from,
            'event' => $this->event,
            'handler' => $this->handler,
        ]);
    }

    public function send(mixed $data)
    {
        Yii::getNatsClient()->publish("module_event", [
            'from' => Module::getCurrentModuleName(),
            'event' => $this->event,
            'data' => $data,
        ]);
    }
}
