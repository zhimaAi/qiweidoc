<a href="https://roadrunner.dev" target="_blank">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
    <img align="center" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8">
  </picture>
</a>

# RoadRunner Jobs Plugin

[![PHP Version Require](https://poser.pugx.org/spiral/roadrunner-jobs/require/php)](https://packagist.org/packages/spiral/roadrunner-jobs)
[![Latest Stable Version](https://poser.pugx.org/spiral/roadrunner-jobs/v/stable)](https://packagist.org/packages/spiral/roadrunner-jobs)
[![phpunit](https://github.com/spiral/roadrunner-jobs/actions/workflows/phpunit.yml/badge.svg)](https://github.com/spiral/roadrunner-jobs/actions)
[![psalm](https://github.com/spiral/roadrunner-jobs/actions/workflows/psalm.yml/badge.svg)](https://github.com/spiral/roadrunner-jobs/actions)
[![Codecov](https://codecov.io/gh/roadrunner-php/jobs/branch/4.x/graph/badge.svg)](https://codecov.io/gh/roadrunner-php/jobs/)
[![Total Downloads](https://poser.pugx.org/spiral/roadrunner-jobs/downloads)](https://packagist.org/packages/spiral/roadrunner-jobs)
[![StyleCI](https://github.styleci.io/repos/388772135/shield?branch=master)](https://github.styleci.io/repos/388772135?branch=master)
<a href="https://discord.gg/spiralphp"><img src="https://img.shields.io/badge/discord-chat-magenta.svg"></a>

This repository contains the codebase PHP bridge using RoadRunner Jobs plugin.

## Installation

To install application server and Jobs codebase

```bash
composer require spiral/roadrunner-jobs
```

You can use the convenient installer to download the latest available compatible version of RoadRunner assembly:

```bash
composer require spiral/roadrunner-cli --dev
vendor/bin/rr get
```

## Configuration

First you need to add at least one jobs adapter to your RoadRunner configuration. For example, such a configuration would be quite feasible to run:

```yaml
rpc:
  listen: tcp://127.0.0.1:6001

server:
  command: php consumer.php
  relay: pipes

jobs:
  consume: [ "local" ]
  pipelines:
    local:
      driver: memory
      config:
        priority: 10
        prefetch: 10000
```

> **Note**
> Read more about all available drivers on the [documentation](https://docs.roadrunner.dev/queues-and-jobs/overview-queues) page.

After starting the server with this configuration, one driver named `local` will be available to you.

## Usage

### Producer

The following code will allow writing and reading an arbitrary value from the RoadRunner server.

```php
<?php

use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\Goridge\RPC\RPC;

require __DIR__ . '/vendor/autoload.php';

// Jobs service
$jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));

// Select "local" pipeline from jobs
$queue = $jobs->connect('local');

// Create task prototype with default headers
$task = $queue->create('ping', '{"site": "https://example.com"}') // Create task with "echo" name
    ->withHeader('attempts', 4) // Number of attempts to execute the task
    ->withHeader('retry-delay', 10); // Delay between attempts

// Push "echo" task to the queue
$task = $queue->dispatch($task);

var_dump($task->getId() . ' has been queued');
```

### Consumer

The Consumer processes tasks from RoadRunner server and responds based on the processing outcome:

- `ack` - is used for positive acknowledgements.
- `nack` - is used for negative acknowledgements.
- `requeue` - is used for requeuing the task.

The behavior of the `nack` method depends on its implementation by the queue driver. It can accept an additional
parameter **redelivery**; if it is passed and set to **true**, the task will be requeued. However, not all drivers 
support this functionality. If the redelivery parameter is not passed, set to **false**, or the queue driver's
implementation does not support it, the task will not be requeued.

```php
$task->nack(message: $reason, redelivery: true);
```

The `requeue` method is implemented by RoadRunner and does not depend on the queue driver. It allows you to resend
the task to **the end of the queue** and add additional headers to the task.

```php
$task->withHeader('attempts', (string) ($attempts + 1))->requeue($exception);
```

The `nack` and `requeue` methods have the ability to specify a **delay** for requeuing the task. To do this, call 
the `withDelay` method and pass the desired value before invoking the `nack` or `requeue` methods.

```php
$task->withDelay(10)->requeue($exception);
```

```php
<?php

use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

require __DIR__ . '/vendor/autoload.php';

$consumer = new Spiral\RoadRunner\Jobs\Consumer();

/** @var Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface $task */
while ($task = $consumer->waitTask()) {
    try {
        $name = $task->getName(); // "ping"
        $queue = $task->getQueue(); // "local"
        $driver = $task->getDriver(); // "memory"
        $payload = $task->getPayload(); // {"site": "https://example.com"}
    
        // Process task

        $task->ack();
    } catch (\Throwable $e) {
        $task->requeue($e);
    }
}
```

<a href="https://spiral.dev/">
<img src="https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg" alt="try Spiral Framework" />
</a>

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).
