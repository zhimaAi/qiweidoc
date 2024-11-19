<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

declare(strict_types=1);

use App\Command\Cron\SchedulerCommand;
use App\Command\Daemon\ChatSessionMessagePullCommand;
use App\Command\HelloCommand;
use App\Command\Once\ChatSessionMediaDownloadCommand;
use App\Command\TestCommand;
use App\Command\TestConsumerCommand;

return [
    'test' => TestCommand::class,
    'test:consumer' => TestConsumerCommand::class,
    'hello' => HelloCommand::class,

    'scheduler:run' => SchedulerCommand::class,
    'chat-session-message-pull' => ChatSessionMessagePullCommand::class,
    'chat-session-media-download' => ChatSessionMediaDownloadCommand::class,
];
