# Yii Network Utilities Change Log

## 1.2.0 September 02, 2024

- New #65: Add `IP_PATTERN` and `IP_REGEXP` constants  to `IpHelper` for checking IP of both IPv4 and IPv6 versions             
  (@arogachev)
- New #65: Add `NEGATION_CHARACTER` constant to `IpRanges` used to negate ranges (@arogachev)
- New #65: Add `isIpv4()`, `isIpv6()`, `isIp()` methods to `IpHelper` (@arogachev)

## 1.1.0 August 06, 2024

- New #63: Add `IpRanges` that represents a set of IP ranges that are either allowed or forbidden (@vjik)
- Bug #59: Fix error while converting IP address to bits representation in PHP 8.0+ (@vjik)

## 1.0.1 January 27, 2022

- Bug #40: Fix return type for callback of `set_error_handler()` function (@devanych)

## 1.0.0 March 04, 2021

- Initial release.
