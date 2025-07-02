<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Network Utilities</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/network-utilities/v)](https://packagist.org/packages/yiisoft/network-utilities)
[![Total Downloads](https://poser.pugx.org/yiisoft/network-utilities/downloads)](https://packagist.org/packages/yiisoft/network-utilities)
[![Build status](https://github.com/yiisoft/network-utilities/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/network-utilities/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/network-utilities/graph/badge.svg?token=LSO6D4QK3O)](https://codecov.io/gh/yiisoft/network-utilities)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fnetwork-utilities%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/network-utilities/master)
[![static analysis](https://github.com/yiisoft/network-utilities/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/network-utilities/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/network-utilities/coverage.svg)](https://shepherd.dev/github/yiisoft/network-utilities)

The package contains various network utilities useful for:

- getting info about IP address;
- checking if IP is in a certain range;
- expanding IPv6;
- converting IP to bits representation;
- checking DNS record availability.

## Requirements

- PHP 7.4 or higher.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/network-utilities
```

## General usage

### `IpHelper`

```php
use Yiisoft\NetworkUtilities\IpHelper;

// Check IP version.
$version = IpHelper::getIpVersion('192.168.1.1');
if ($version === IpHelper::IPV4) {
    // ...
}

// Check if IP is in a certain range.
if (!IpHelper::inRange('192.168.1.21/32', '192.168.1.0/24')) {
    throw new \RuntimeException('Access denied!');
}

// Expand IP v6.
echo IpHelper::expandIPv6('2001:db8::1');

// Convert IP to bits representation.
echo IpHelper::ip2bin('192.168.1.1');

// Get bits from CIDR Notation.
echo IpHelper::getCidrBits('192.168.1.21/32');
```

### `DnsHelper`

```php
use Yiisoft\NetworkUtilities\DnsHelper;

// Check DNS record availability.
if (!DnsHelper::existsA('yiiframework.com')) {
  // Record not found.
}
```

### `IpRanges`

```php
use Yiisoft\NetworkUtilities\IpRanges;

$ipRanges = new IpRanges(
    [
        '10.0.1.0/24',
        '2001:db0:1:2::/64',
        IpRanges::LOCALHOST,
        'myNetworkEu',
        '!' . IpRanges::ANY,
    ],
    [
        'myNetworkEu' => ['1.2.3.4/10', '5.6.7.8'],
    ],
);

$ipRanges->isAllowed('10.0.1.28/28'); // true
$ipRanges->isAllowed('1.2.3.4'); // true
$ipRanges->isAllowed('192.168.0.1'); // false
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Network Utilities is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
