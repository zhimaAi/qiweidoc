<?php

declare(strict_types=1);

namespace Yiisoft\NetworkUtilities;

use InvalidArgumentException;

use function array_key_exists;
use function array_merge;
use function array_unique;
use function strlen;
use function strpos;

/**
 * `IpRanges` represents a set of IP ranges that are either allowed or forbidden.
 */
final class IpRanges
{
    public const ANY = 'any';
    public const PRIVATE = 'private';
    public const MULTICAST = 'multicast';
    public const LINK_LOCAL = 'linklocal';
    public const LOCALHOST = 'localhost';
    public const DOCUMENTATION = 'documentation';
    public const SYSTEM = 'system';
    /**
     * Negation character used to negate ranges
     */
    public const NEGATION_CHARACTER = '!';

    /**
     * Default network aliases.
     * @see https://datatracker.ietf.org/doc/html/rfc5735#section-4
     */
    public const DEFAULT_NETWORKS = [
        '*' => [self::ANY],
        self::ANY => ['0.0.0.0/0', '::/0'],
        self::PRIVATE => ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fd00::/8'],
        self::MULTICAST => ['224.0.0.0/4', 'ff00::/8'],
        self::LINK_LOCAL => ['169.254.0.0/16', 'fe80::/10'],
        self::LOCALHOST => ['127.0.0.0/8', '::1'],
        self::DOCUMENTATION => ['192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24', '2001:db8::/32'],
        self::SYSTEM => [self::MULTICAST, self::LINK_LOCAL, self::LOCALHOST, self::DOCUMENTATION],
    ];

    /**
     * @var string[]
     */
    private array $ranges;

    /**
     * @psalm-var array<string, list<string>>
     */
    private array $networks;

    /**
     * @param string[] $ranges The IPv4 or IPv6 ranges that are either allowed or forbidden.
     *
     * The following preparation tasks are performed:
     *  - recursively substitute aliases (described in {@see $networks}) with their values;
     *  - remove duplicates.
     *
     * When the array is empty or the option is not set, all IP addresses are allowed.
     *
     * Otherwise, the rules are checked sequentially until the first match is found. An IP address is forbidden
     * when it hasn't matched any of the rules.
     *
     * Example:
     *
     * ```php
     * new Ip(ranges: [
     *     '192.168.10.128'
     *     '!192.168.10.0/24',
     *     'any' // allows any other IP addresses
     * ]);
     * ```
     *
     * In this example, access is allowed for all the IPv4 and IPv6 addresses excluding the `192.168.10.0/24`
     * subnet. IPv4 address `192.168.10.128` is also allowed, because it is listed before the restriction.
     * @param array $networks Custom network aliases, that can be used in {@see $ranges}:
     *   - key - alias name;
     *   - value - array of strings. String can be an IP range, IP address or another alias. String can be negated
     *     with `!` character.
     * The default aliases are defined in {@see self::DEFAULT_NETWORKS} and will be merged with custom ones.
     *
     * @psalm-param array<string, list<string>> $networks
     */
    public function __construct(array $ranges = [], array $networks = [])
    {
        foreach ($networks as $key => $_values) {
            if (array_key_exists($key, self::DEFAULT_NETWORKS)) {
                throw new InvalidArgumentException("Network alias \"{$key}\" already set as default.");
            }
        }
        $this->networks = array_merge(self::DEFAULT_NETWORKS, $networks);

        $this->ranges = $this->prepareRanges($ranges);
    }

    /**
     * Get the IPv4 or IPv6 ranges that are either allowed or forbidden.
     *
     * @return string[] The IPv4 or IPv6 ranges that are either allowed or forbidden.
     */
    public function getRanges(): array
    {
        return $this->ranges;
    }

    /**
     * Get network aliases, that can be used in {@see $ranges}.
     *
     * @return array Network aliases.
     *
     * @see $networks
     */
    public function getNetworks(): array
    {
        return $this->networks;
    }

    /**
     * Whether the IP address with specified CIDR is allowed according to the {@see $ranges} list.
     */
    public function isAllowed(string $ip): bool
    {
        if (empty($this->ranges)) {
            return true;
        }

        foreach ($this->ranges as $string) {
            [$isNegated, $range] = $this->parseNegatedRange($string);
            if (IpHelper::inRange($ip, $range)) {
                return !$isNegated;
            }
        }

        return false;
    }

    /**
     * Prepares array to fill in {@see $ranges}:
     *  - recursively substitutes aliases, described in `$networks` argument with their values;
     *  - removes duplicates.
     *
     * @param string[] $ranges
     * @return string[]
     */
    private function prepareRanges(array $ranges): array
    {
        $result = [];
        foreach ($ranges as $string) {
            [$isRangeNegated, $range] = $this->parseNegatedRange($string);
            if (isset($this->networks[$range])) {
                $replacements = $this->prepareRanges($this->networks[$range]);
                foreach ($replacements as &$replacement) {
                    [$isReplacementNegated, $replacement] = $this->parseNegatedRange($replacement);
                    $result[] = ($isRangeNegated && !$isReplacementNegated ? self::NEGATION_CHARACTER : '')
                        . $replacement;
                }
            } else {
                $result[] = $string;
            }
        }

        return array_unique($result);
    }

    /**
     * Parses IP address/range for the negation with `!`.
     *
     * @return array The result array consists of 2 elements:
     *   - `boolean` - whether the string is negated;
     *   - `string` - the string without negation (when the negation were present).
     *
     * @psalm-return array{0: bool, 1: string}
     */
    private function parseNegatedRange(string $string): array
    {
        $isNegated = strpos($string, self::NEGATION_CHARACTER) === 0;
        return [$isNegated, $isNegated ? substr($string, strlen(self::NEGATION_CHARACTER)) : $string];
    }
}
