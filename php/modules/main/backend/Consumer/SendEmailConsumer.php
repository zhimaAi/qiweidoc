<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

class SendEmailConsumer
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(): void
    {
        echo "sending email to {$this->email}...\n";
    }
}
