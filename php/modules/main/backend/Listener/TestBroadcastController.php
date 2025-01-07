<?php

namespace Modules\Main\Listener;

class TestBroadcastController
{
    public function __construct(
        private string $from,
        private string $payload
    ) {}

    public function handle()
    {
        echo sprintf("收到来自%s的消息:%s\n", $this->from, $this->payload);
    }
}
