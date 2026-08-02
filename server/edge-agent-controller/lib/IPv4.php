<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use InvalidArgumentException;

final class IPv4
{
    /** @return array{network:int,broadcast:int,bits:int,canonical:string} */
    public static function parsePrefix(string $value, int $minimumBits = 0, int $maximumBits = 32): array
    {
        $parts = explode('/', trim($value), 2);
        if (count($parts) !== 2 || !ctype_digit($parts[1])) {
            throw new InvalidArgumentException("invalid IPv4 prefix $value");
        }
        $bits = (int) $parts[1];
        if ($bits < $minimumBits || $bits > $maximumBits) {
            throw new InvalidArgumentException("IPv4 prefix $value must be between /$minimumBits and /$maximumBits");
        }
        $address = self::toInteger($parts[0]);
        $mask = $bits === 0 ? 0 : ((0xffffffff << (32 - $bits)) & 0xffffffff);
        $network = $address & $mask;
        $broadcast = $network | (~$mask & 0xffffffff);
        return [
            'network' => $network,
            'broadcast' => $broadcast,
            'bits' => $bits,
            'canonical' => self::fromInteger($network) . '/' . $bits,
        ];
    }

    public static function normalizeAddress(string $value): string
    {
        return self::fromInteger(self::toInteger($value));
    }

    public static function contains(string $prefix, string $address, bool $usableOnly = false): bool
    {
        $parsed = self::parsePrefix($prefix);
        $number = self::toInteger($address);
        if ($number < $parsed['network'] || $number > $parsed['broadcast']) {
            return false;
        }
        return !$usableOnly || ($number > $parsed['network'] && $number < $parsed['broadcast']);
    }

    public static function overlaps(string $left, string $right): bool
    {
        $a = self::parsePrefix($left);
        $b = self::parsePrefix($right);
        return $a['network'] <= $b['broadcast'] && $b['network'] <= $a['broadcast'];
    }

    /** @param list<string> $used */
    public static function firstUsableAddress(string $prefix, array $used = [], array $reserved = []): string
    {
        $parsed = self::parsePrefix($prefix, 0, 30);
        $blocked = [];
        foreach (array_merge($used, $reserved) as $address) {
            $blocked[self::normalizeAddress($address)] = true;
        }
        for ($candidate = $parsed['network'] + 1; $candidate < $parsed['broadcast']; $candidate++) {
            $address = self::fromInteger($candidate);
            if (!isset($blocked[$address])) {
                return $address;
            }
        }
        throw new InvalidArgumentException("IPv4 prefix {$parsed['canonical']} is exhausted");
    }

    /** @param list<string> $usedPrefixes */
    public static function firstSubnet(string $pool, int $subnetBits, array $usedPrefixes = []): string
    {
        $parsed = self::parsePrefix($pool, 0, 30);
        if ($subnetBits < $parsed['bits'] || $subnetBits > 30) {
            throw new InvalidArgumentException('agent prefix length is outside overlay pool');
        }
        $used = [];
        foreach ($usedPrefixes as $prefix) {
            $used[self::parsePrefix($prefix)['canonical']] = true;
        }
        $step = 1 << (32 - $subnetBits);
        for ($candidate = $parsed['network']; $candidate + $step - 1 <= $parsed['broadcast']; $candidate += $step) {
            $prefix = self::fromInteger($candidate) . '/' . $subnetBits;
            if (!isset($used[$prefix])) {
                return $prefix;
            }
        }
        throw new InvalidArgumentException("overlay pool {$parsed['canonical']} is exhausted");
    }

    private static function toInteger(string $value): int
    {
        $packed = @inet_pton(trim($value));
        if ($packed === false || strlen($packed) !== 4) {
            throw new InvalidArgumentException("invalid IPv4 address $value");
        }
        return (int) unpack('Naddress', $packed)['address'];
    }

    private static function fromInteger(int $value): string
    {
        return (string) inet_ntop(pack('N', $value & 0xffffffff));
    }
}
