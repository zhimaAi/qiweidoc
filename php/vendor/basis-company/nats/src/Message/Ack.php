<?php

declare(strict_types=1);

namespace Basis\Nats\Message;

class Ack extends Prototype
{
    public string $subject;

    public function render(): string
    {
        $payload = Payload::parse('')->render();
        return "PUB $this->subject  $payload";
    }
}
