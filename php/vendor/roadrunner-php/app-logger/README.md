# Package for sending log messages to RoadRunner


[![PHP Version Require](https://poser.pugx.org/roadrunner-php/app-logger/require/php)](https://packagist.org/packages/roadrunner-php/app-logger)
[![Latest Stable Version](https://poser.pugx.org/roadrunner-php/app-logger/v/stable)](https://packagist.org/packages/roadrunner-php/app-logger)
[![phpunit](https://github.com/roadrunner-php/app-logger/actions/workflows/phpunit.yml/badge.svg)](https://github.com/roadrunner-php/app-logger/actions)
[![psalm](https://github.com/roadrunner-php/app-logger/actions/workflows/psalm.yml/badge.svg)](https://github.com/roadrunner-php/app-logger/actions)
[![Codecov](https://codecov.io/gh/roadrunner-php/app-logger/branch/master/graph/badge.svg)](https://codecov.io/gh/roadrunner-php/app-logger/)
[![Total Downloads](https://poser.pugx.org/roadrunner-php/app-logger/downloads)](https://packagist.org/packages/roadrunner-php/app-logger)

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+

## Installation

You can install the package via composer:

```bash
composer require roadrunner-php/app-logger
```

## Usage

Such a configuration would be quite feasible to run:

```yaml
rpc:
  listen: tcp://127.0.0.1:6001

logs:
  channels:
    app:
      level: info
```

Then you need to create an instance of `RoadRunner\Logger\Logger`

```PHP
use Spiral\Goridge\RPC\RPC;
use RoadRunner\Logger\Logger;

$rpc = RPC::create('tcp://127.0.0.1:6001');
// or
$rpc = RPC::fromGlobals();
// or
$rpc = RPC::fromEnvironment(new \Spiral\RoadRunner\Environment([
    'RR_RPC' => 'tcp://127.0.0.1:6001'
]));

$logger = new Logger($rpc);
```

## Available methods
```debug```, ```error```, ```info```, ```warning``` is RoadRunner logger, and ```log``` is stderr
```PHP
/**
 * debug mapped to RR's debug logger
 */
$logger->debug('Debug message');

/**
 * error mapped to RR's error logger
 */
$logger->error('Error message');

/**
 * log mapped to RR's stderr
 */
$logger->log("Log message \n");

/**
 * info mapped to RR's info logger
 */
$logger->info('Info message');

/**
 * warning mapped to RR's warning logger
 */
$logger->warning('Warning message');
```

<a href="https://spiral.dev/">
<img src="https://user-images.githubusercontent.com/773481/220979012-e67b74b5-3db1-41b7-bdb0-8a042587dedc.jpg" alt="try Spiral Framework" />
</a>

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information. Maintained
by [Spiral Scout](https://spiralscout.com).
