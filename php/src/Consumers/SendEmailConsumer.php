<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Consumers;

use App\Libraries\Core\Consumer\BaseConsumer;

class SendEmailConsumer extends BaseConsumer
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(): void
    {
        echo "start\n";
        sleep(3);
        echo "end\n";
    }
}
