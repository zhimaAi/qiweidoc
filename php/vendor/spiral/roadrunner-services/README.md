<a href="https://roadrunner.dev" target="_blank">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://github.com/roadrunner-server/.github/assets/8040338/e6bde856-4ec6-4a52-bd5b-bfe78736c1ff">
    <img align="center" src="https://github.com/roadrunner-server/.github/assets/8040338/040fb694-1dd3-4865-9d29-8e0748c2c8b8">
  </picture>
</a>

# Roadrunner services manager

[![PHP Version Require](https://poser.pugx.org/spiral/roadrunner-services/require/php)](https://packagist.org/packages/spiral/roadrunner-services)
[![Latest Stable Version](https://poser.pugx.org/spiral/roadrunner-services/v/stable)](https://packagist.org/packages/spiral/roadrunner-services)
[![phpunit](https://github.com/spiral/roadrunner-services/actions/workflows/phpunit.yml/badge.svg)](https://github.com/spiral/roadrunner-services/actions)
[![psalm](https://github.com/spiral/roadrunner-services/actions/workflows/psalm.yml/badge.svg)](https://github.com/spiral/roadrunner-services/actions)
[![Total Downloads](https://poser.pugx.org/spiral/roadrunner-services/downloads)](https://packagist.org/packages/spiral/roadrunner-services)

This package will help you to manage [Roadrunner services](https://docs.roadrunner.dev/plugins/service)

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+

## Installation

You can install the package via composer:

```bash
composer require spiral/roadrunner-services
```

## Usage

Such a configuration would be quite feasible to run:

```yaml
rpc:
  listen: tcp://127.0.0.1:6001

service: {}
```

Then you need to create an instance of `Spiral\RoadRunner\Services\Manager`

```php
use Spiral\RoadRunner\Services\Manager;
use Spiral\Goridge\RPC\RPC;

$rpc = RPC::create('tcp://127.0.0.1:6001'));
$manager = new Manager($rpc);
```

### Create a new service

```php
use Spiral\RoadRunner\Services\Exception\ServiceException;

try {
    $result = $manager->create(
        name: 'listen-jobs', 
        command: 'php app.php queue:listen',
        processNum: 3,
        execTimeout: 0,
        remainAfterExit: false,
        env: ['APP_ENV' => 'production'],
        restartSec: 30
    );
    
    if (!$result) {
        throw new ServiceException('Service creation failed.');
    }
} catch (ServiceException $e) {
    // handle exception
}
```

### Check service status

```php
use Spiral\RoadRunner\Services\Exception\ServiceException;

try {
    $status = $manager->statuses(name: 'listen-jobs');
    
    // Will return an array with statuses of every run process
    // [
    //    [
    //      'cpu_percent' => 59.5,
    //      'pid' => 33,
    //      'memory_usage' => 200,
    //      'command' => 'foo/bar',
    //      'error' => null
    //    ],
    //    [
    //      'cpu_percent' => 60.2,
    //      'pid' => 34,
    //      'memory_usage' => 189,
    //      'command' => 'foo/bar'
    //      'error' => [
    //          'code' => 1,
    //          'message' => 'Process exited with code 1'
    //          'details' => [...] // array with details
    //      ]
    //    ],
    // ] 
} catch (ServiceException $e) {
    // handle exception
}
```

### Restart service

```php
use Spiral\RoadRunner\Services\Exception\ServiceException;

try {
    $result = $manager->restart(name: 'listen-jobs');
    
    if (!$result) {
        throw new ServiceException('Service restart failed.');
    }
} catch (ServiceException $e) {
    // handle exception
}
```

### Terminate service

```php
use Spiral\RoadRunner\Services\Exception\ServiceException;

try {
    $result = $manager->terminate(name: 'listen-jobs');
    
    if (!$result) {
        throw new ServiceException('Service termination failed.');
    }
} catch (ServiceException $e) {
    // handle exception
}
```

### List of all services

```php
use Spiral\RoadRunner\Services\Exception\ServiceException;

try {
    $services = $manager->list();
    
    // Will return an array with services names
    // ['listen-jobs', 'websocket-connection'] 
} catch (ServiceException $e) {
    // handle exception
}
```
