# curl-builder [![build](https://github.com/alexkart/curl-builder/actions/workflows/php.yml/badge.svg)](https://github.com/alexkart/curl-builder/actions/workflows/php.yml) [![Code Coverage](https://scrutinizer-ci.com/g/alexkart/curl-builder/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/alexkart/curl-builder/?branch=master)

curl-builder is a curl command generator which can generate curl commands automatically from PSR-7 server requests and manually by
specifying options and URL.

## Installation

```bash
composer require alexkart/curl-builder
```

## Examples

### Generating curl command from PSR-7 request
```php
$request = new Request('POST', 'http://example.com', [
    'Connection' => ['keep-alive'],
    'Accept' => [
        'text/html',
        'application/xhtml+xml',
    ],
], 'data');
$command = new Command();
$command->setRequest($request);
$curl = $command->build();
// curl -H 'Connection: keep-alive' -H 'Accept: text/html, application/xhtml+xml' -d 'data' http://example.com
```

### Constructing curl command manually
```php
$command = new Command();
$command->setUrl('http://example.com');
$command->addOption('-v');
$command->addOption('-H', 'Connection: keep-alive');
$command->addOption('-H', 'Cache-Control: max-age=0');
// curl -v -H 'Connection: keep-alive' -H 'Cache-Control: max-age=0' http://example.com
```

### Adding options

Options can be added to the command one by one with `addOption()`
```php
$command->addOption('-L');
$command->addOption('-v');
$command->addOption('-H', 'Connection: keep-alive');
// curl -L -v -H 'Connection: keep-alive' ...
```

or add several of them at once with `addOptions()`
```php
$command->addOption('-v');
$command->addOptions([
    '-L',
    '-d' => 'test'
]);
// curl -v -L -d 'test' ...
```

`setOptions()` can be used to override previously set options
```php
$command->setOptions(['-L', '-v']);
// curl -L -v ...
```

`addOptions()` and `setOptions()` formats:
```php
// options without arguments
// the following lines will generate the same command
$command->setOptions(['-L' => [null], '-v' => [null]]);
$command->setOptions(['-L' => null, '-v' => null]);
$command->setOptions(['-L', '-v']);
// curl -L -v ... 

// options with arguments
$command->setOptions(['-H' => 'test']);
// curl -H 'test' ...
$command->setOptions(['-H' => ['test1', 'test2']]);
// curl -H 'test1' -H 'test2'
```

### Specifying command template
Default template for the command is `{name}{option}{url}`. But you can change it with `setTemplate()` method
```php
$command = new Command();
$command->setUrl('http://example.com');
$command->addOption('-v');
$command->addOption('-L');
$curl = $command->build();
// curl -v -L http://example.com

// change order
$command->setTemplate(Command::TEMPLATE_COMMAND_NAME . Command::TEMPLATE_URL . Command::TEMPLATE_OPTIONS);
$curl = $command->build();
// curl http://example.com -v -L

// remove options
$command->setTemplate(Command::TEMPLATE_COMMAND_NAME . Command::TEMPLATE_URL);
$curl = $command->build();
// curl http://example.com
```

### Quoting and escaping arguments
By default arguments are quoted with single quotes and if single quote appears in the argument it will be escaped
```php
$command->addOption('-d', 'data');
// curl -d 'data'

$command->addOption('-d', "data'1");
// curl -d $'data\'1'
```

Quoting character can be changed to double quote or removed
```php
$command->addOption('-d', 'data1');
$command->addOption('-d', 'data"2');
$command->setQuoteCharacter(Command::QUOTE_DOUBLE);
// curl -d "data" -d "data\"2"

$command->addOption('-d', 'data');
$command->setQuoteCharacter(Command::QUOTE_NONE);
// curl -d data

$command->addOption('-d', 'value with spaces');
$command->setQuoteCharacter(Command::QUOTE_NONE);
// curl -d value\ with\ spaces
``` 
