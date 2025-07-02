<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Security</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/security/v/stable.png)](https://packagist.org/packages/yiisoft/security)
[![Total Downloads](https://poser.pugx.org/yiisoft/security/downloads.png)](https://packagist.org/packages/yiisoft/security)
[![Build Status](https://github.com/yiisoft/security/workflows/build/badge.svg)](https://github.com/yiisoft/security/actions)
[![Code Coverage](https://codecov.io/gh/yiisoft/security/graph/badge.svg?token=PLDTLEJ782)](https://codecov.io/gh/yiisoft/security)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fsecurity%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/security/master)
[![static analysis](https://github.com/yiisoft/security/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/security/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/security/coverage.svg)](https://shepherd.dev/github/yiisoft/security)

Security package provides a set of classes to handle common security-related tasks:

- Random values generation
- Password hashing and validation
- Encryption and decryption
- Data tampering prevention
- Masking token length

## Requirements

- PHP 8.0 or higher.
- `hash` PHP extension.
- `openssl` PHP extension.
- `random` PHP extension.

## Installation

```
composer require yiisoft/security
```

## General usage

### Random values generation

In order to generate a string that is 42 characters long use:

```php
$randomString = Random::string(42);
```

The following extras are available via PHP directly:

- `random_bytes()` for bytes. Note that output may not be ASCII.
- `random_int()` for integers.

### Password hashing and validation

Working with passwords includes two steps. Saving password hashes:
 
```php
$hash = (new PasswordHasher())->hash($password);

// save hash to database or another storage
saveHash($hash); 
```

Validating password against the hash:

```php
// obtain hash from database or another storage
$hash = getHash();

$result = (new PasswordHasher())->validate($password, $hash); 
```

### Encryption and decryption by password

Encrypting data:

```php
$encryptedData = (new Crypt())->encryptByPassword($data, $password);

// save data to database or another storage
saveData($encryptedData);
```

Decrypting it:

```php
// obtain encrypted data from database or another storage
$encryptedData = getEncryptedData();

$data = (new Crypt())->decryptByPassword($encryptedData, $password);
```

### Encryption and decryption by key

Encrypting data:

```php
$encryptedData = (new Crypt())->encryptByKey($data, $key);

// save data to database or another storage
saveData($encryptedData);
```

Decrypting it:

```php
// obtain encrypted data from database or another storage
$encryptedData = getEncryptedData();

$data = (new Crypt())->decryptByKey($encryptedData, $key);
```

### Data tampering prevention

MAC signing could be used in order to prevent data tampering. The `$key` should be present at both sending and receiving
sides. At the sending side:

```php
$signedMessage = (new Mac())->sign($message, $key);

sendMessage($signedMessage);
```

At the receiving side:

```php
$signedMessage = receiveMessage($signedMessage);

try {
    $message = (new Mac())->getMessage($signedMessage, $key);
} catch (\Yiisoft\Security\DataIsTamperedException $e) {
    // data is tampered
}
```

### Masking token length

Masking a token helps to mitigate BREACH attack by randomizing how token outputted on each request.
A random mask applied to the token making the string always unique.

In order to mask a token:

```php
$maskedToken = \Yiisoft\Security\TokenMask::apply($token);
```

In order to get original value from the masked one:

```php
$token = \Yiisoft\Security\TokenMask::remove($maskedToken);
```

### Native PHP functionality

Additionally to this library methods, there is a set of handy native PHP methods.

#### Timing attack resistant string comparison

Comparing strings as usual is not secure when dealing with user inputed passwords or key phrases. Usual string comparison
return as soon as a difference between the strings is found so attacker could efficiently brute-force character by character
going to the next one as soon as response time increases.

There is a special function in PHP that compares strings in a constant time:

```php
hash_equals($expected, $actual);
```

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Security is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
