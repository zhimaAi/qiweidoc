<?php

namespace Common;

use Basis\Nats\Message\Payload;
use Exception;

class Micro
{
    private string $name;
    private string $handler;

    public static function name(string $name)
    {
        $obj = new self();
        $obj->name = $name;

        return $obj;
    }

    public function action(string $className)
    {
        $this->handler = $className;

        return $this;
    }

    public function register()
    {
        Yii::getRpcClient()->call('micro.AddEndpoint', [
            'name' => $this->name,
            'handler' => $this->handler,
        ]);
    }

    public static function call(string $moduleName, string $serviceName, string $data, int $timeout = 1): array
    {
        $name = "{$moduleName}.{$serviceName}";
        $body = null;
        Yii::getNatsClient($timeout)->request($name, $data, function (Payload $payload) use (&$body) {
            $body = $payload->body;
        });
        if (is_null($body)) {
             throw new Exception("请求超时");
        }
        $result = json_decode($body, true);
        if (!empty($result['error'])) {
            throw new Exception($result['error']);
        }
        return $result;
    }
}
