<?php

namespace Common;

use Closure;
use GuzzleHttp\Promise\Utils;

class Broadcast
{
    private string $from;

    private string $event;

    public Closure $handler;

    private string $payload;

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

    public function send(string $payload)
    {
        // 先获取出当前模块外的其它所有已启用的模块
        $moduleNameList = Module::getModuleDirectories();
        $moduleNameList = array_filter($moduleNameList, function ($name) {
            return $name != Module::getCurrentModuleName();
        });
        $modules = [];
        foreach ($moduleNameList as $name) {
            $module = Module::getModuleConfig($name);
            if ($module['paused']) {
                continue;
            }
            $modules[] = $module;
        }

        $client = new HttpClient([]);
        foreach ($modules as $module) {
            $promises[] = $client->postAsync("http://localhost:{$module['http_port']}/broadcast", [
                'from' => Module::getCurrentModuleName(),
                'event' => $this->event,
                'payload' => $payload,
            ]);
        }
        if (!empty($promises)) {
            Utils::all($promises)->wait();
        }
    }

    public function handle(Closure $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    public static function parse(string $data): ?self
    {
        $data = json_decode($data, true);
        if (!empty($data['from']) && !empty($data['event']) && !empty($data['payload'])) {
            $result = new self();
            $result->event = $data['event'];
            $result->from = $data['from'];
            $result->payload = $data['payload'];
            return $result;
        } else {
            return null;
        }
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getHandler(): Closure
    {
        return $this->handler;
    }

    public static function dispatch(Broadcast $broadcast)
    {
        $routers = Module::getRouterProvider()->getBroadcastRouters();
        foreach ($routers as $router) {
            if ($router->getFrom() == $broadcast->getFrom() && $router->getEvent() == $broadcast->getEvent()) {
                call_user_func($router->getHandler(), $broadcast->getPayload());
            }
        }
    }
}
