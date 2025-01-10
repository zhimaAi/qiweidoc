<?php

namespace Modules\Main\Micro;

class TestMirco
{
    public function __construct(private string $payload)
    {

    }

    public function handle(): array
    {
        // sleep(5);
        return [
            'response' => "hello, {$this->payload}",
            "aaa" => 111
        ];
    }
}
