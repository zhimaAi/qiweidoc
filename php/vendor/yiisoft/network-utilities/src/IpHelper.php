<?php

declare(strict_types=1);

namespace Yiisoft\NetworkUtilities;

use InvalidArgumentException;
use RuntimeException;

use function assert;
use function is_string;
use function strlen;

/**
 * `IpHelper` contains static methods to work with IPs.
 */
final class IpHelper
{
    public const IPV4 = 4;
    public const IPV6 = 6;

    /**
     * IPv4 address pattern. This pattern is PHP and JavaScript compatible.
     * Allows to define your own IP regexp e.g. `'/^'.IpHelper::IPV4_PATTERN.'/(\d+)$/'`.
     */
    public const IPV4_PATTERN = '((2(5[0-5]|[0-4]\d)|1\d{2}|[1-9]?\d)\.){3}(2(5[0-5]|[0-4]\d)|1\d{2}|[1-9]?\d)';
    /**
     * IPv6 address regexp. This regexp is PHP and Javascript compatible.
     */
    public const IPV4_REGEXP = '/^' . self::IPV4_PATTERN . '$/';
    /**
     * IPv6 address pattern. This pattern is PHP and Javascript compatible.
     * Allows to define your own IP regexp e.g. `'/^'.IpHelper::IPV6_PATTERN.'/(\d+)$/'`.
     */
    public const IPV6_PATTERN = '(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:' . self::IPV4_PATTERN . ')';
    /**
     * IPv6 address regexp. This regexp is PHP and JavaScript compatible.
     */
    public const IPV6_REGEXP = '/^' . self::IPV6_PATTERN . '$/';
    /**
     * The length of IPv6 address in bits.
     */
    public const IPV6_ADDRESS_LENGTH = 128;
    /**
     * The length of IPv4 address in bits.
     */
    public const IPV4_ADDRESS_LENGTH = 32;
    /**
     * IP address pattern (for both IPv4 and IPv6 versions). This pattern is PHP and Javascript compatible.
     * Allows to define your own IP regexp.
     */
    public const IP_PATTERN = '((' . self::IPV4_PATTERN . ')|(' . self::IPV6_PATTERN . '))';
    /**
     * IP address regexp (for both IPv4 and IPv6 versions). This regexp is PHP and JavaScript compatible.
     */
    public const IP_REGEXP = '/^' . self::IP_PATTERN . '$/';

    public static function isIpv4(string $value): bool
    {
        return preg_match(self::IPV4_REGEXP, $value) === 1;
    }

    public static function isIpv6(string $value): bool
    {
        return preg_match(self::IPV6_REGEXP, $value) === 1;
    }

    public static function isIp(string $value): bool
    {
        return preg_match(self::IP_REGEXP, $value) === 1;
    }

    /**
     * Gets the IP version.
     *
     * @param string $ip The valid IPv4 or IPv6 address.
     * @param bool $validate Enable perform IP address validation. False is best practice if the data comes from a trusted source.
     *
     * @return int Value of either {@see IPV4} or {@see IPV6} constant.
     */
    public static function getIpVersion(string $ip, bool $validate = true): int
    {
        $ipStringLength = strlen($ip);
        if ($ipStringLength < 2) {
            throw new InvalidArgumentException("Unrecognized address $ip", 10);
        }
        $preIpVersion = strpos($ip, ':') === false ? self::IPV4 : self::IPV6;
        if ($preIpVersion === self::IPV4 && $ipStringLength < 7) {
            throw new InvalidArgumentException("Unrecognized address $ip", 11);
        }
        if (!$validate) {
            return $preIpVersion;
        }
        $rawIp = @inet_pton($ip);
        if ($rawIp !== false) {
            return strlen($rawIp) === self::IPV4_ADDRESS_LENGTH >> 3 ? self::IPV4 : self::IPV6;
        }
        if ($preIpVersion === self::IPV6 && preg_match(self::IPV6_REGEXP, $ip) === 1) {
            return self::IPV6;
        }
        throw new InvalidArgumentException("Unrecognized address $ip.", 12);
    }

    /**
     * Checks whether IP address or subnet $subnet is contained by $subnet.
     *
     * For example, the following code checks whether subnet `192.168.1.0/24` is in subnet `192.168.0.0/22`:
     *
     * ```php
     * IpHelper::inRange('192.168.1.0/24', '192.168.0.0/22'); // true
     * ```
     *
     * In case you need to check whether a single IP address `192.168.1.21` is in the subnet `192.168.1.0/24`,
     * you can use any of theses examples:
     *
     * ```php
     * IpHelper::inRange('192.168.1.21', '192.168.1.0/24'); // true
     * IpHelper::inRange('192.168.1.21/32', '192.168.1.0/24'); // true
     * ```
     *
     * @param string $subnet The valid IPv4 or IPv6 address or CIDR range, e.g.: `10.0.0.0/8` or `2001:af::/64`.
     * @param string $range The valid IPv4 or IPv6 CIDR range, e.g. `10.0.0.0/8` or `2001:af::/64`.
     *
     * @return bool Whether $subnet is contained by $range.
     *
     * @see https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing
     */
    public static function inRange(string $subnet, string $range): bool
    {
        [$ip, $mask] = array_pad(explode('/', $subnet), 2, null);
        [$net, $netMask] = array_pad(explode('/', $range), 2, null);

        assert(is_string($ip));
        assert(is_string($net));

        $ipVersion = self::getIpVersion($ip);
        $netVersion = self::getIpVersion($net);
        if ($ipVersion !== $netVersion) {
            return false;
        }

        $maxMask = $ipVersion === self::IPV4 ? self::IPV4_ADDRESS_LENGTH : self::IPV6_ADDRESS_LENGTH;
        $mask ??= $maxMask;
        $netMask ??= $maxMask;

        $binIp = self::ip2bin($ip);
        $binNet = self::ip2bin($net);
        $masked = substr($binNet, 0, (int) $netMask);

        return ($masked === '' || strpos($binIp, $masked) === 0) && $mask >= $netMask;
    }

    /**
     * Expands an IPv6 address to it's full notation.
     *
     * For example `2001:db8::1` will be expanded to `2001:0db8:0000:0000:0000:0000:0000:0001`.
     *
     * @param string $ip The original valid IPv6 address.
     *
     * @return string The expanded IPv6 address.
     */
    public static function expandIPv6(string $ip): string
    {
        $ipRaw = @inet_pton($ip);
        if ($ipRaw === false) {
            if (@inet_pton('::1') === false) {
                throw new RuntimeException('IPv6 is not supported by inet_pton()!');
            }
            throw new InvalidArgumentException("Unrecognized address $ip.");
        }

        /** @psalm-var array{hex:string} $hex */
        $hex = unpack('H*hex', $ipRaw);

        return substr(preg_replace('/([a-f0-9]{4})/i', '$1:', $hex['hex']), 0, -1);
    }

    /**
     * Converts IP address to bits representation.
     *
     * @param string $ip The valid IPv4 or IPv6 address.
     *
     * @return string Bits as a string.
     */
    public static function ip2bin(string $ip): string
    {
        if (self::getIpVersion($ip) === self::IPV4) {
            $ipBinary = pack('N', ip2long($ip));
        } elseif (@inet_pton('::1') === false) {
            throw new RuntimeException('IPv6 is not supported by inet_pton()!');
        } else {
            $ipBinary = inet_pton($ip);
        }

        assert(is_string($ipBinary));

        $result = '';
        for ($i = 0, $iMax = strlen($ipBinary); $i < $iMax; $i += 4) {
            $data = substr($ipBinary, $i, 4);
            if (empty($data)) {
                throw new RuntimeException('An error occurred while converting IP address to bits representation.');
            }
            /** @psalm-suppress MixedArgument */
            $result .= str_pad(decbin(unpack('N', $data)[1]), 32, '0', STR_PAD_LEFT);
        }
        return $result;
    }

    /**
     * Gets the bits from CIDR Notation.
     *
     * @param string $ip IP or IP with CIDR Notation (`127.0.0.1`, `2001:db8:a::123/64`).
     *
     * @return int Bits.
     */
    public static function getCidrBits(string $ip): int
    {
        if (preg_match('/^(?<ip>.{2,}?)(?:\/(?<bits>-?\d+))?$/', $ip, $matches) === 0) {
            throw new InvalidArgumentException("Unrecognized address $ip.", 1);
        }
        $ipVersion = self::getIpVersion($matches['ip']);
        $maxBits = $ipVersion === self::IPV6 ? self::IPV6_ADDRESS_LENGTH : self::IPV4_ADDRESS_LENGTH;
        $bits = $matches['bits'] ?? null;
        if ($bits === null) {
            return $maxBits;
        }
        $bits = (int) $bits;
        if ($bits < 0) {
            throw new InvalidArgumentException('The number of CIDR bits cannot be negative.', 2);
        }
        if ($bits > $maxBits) {
            throw new InvalidArgumentException("CIDR bits is greater than $bits.", 3);
        }
        return $bits;
    }
}
