<?php

declare(strict_types=1);

use SesameWare\RbtAgent\IPv4;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/IPv4.php';

function ipv4AssertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function ipv4AssertThrows(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }
    fwrite(STDERR, $message . "\n");
    exit(1);
}

ipv4AssertSame('10.220.16.0/20', IPv4::parsePrefix('10.220.17.91/20')['canonical'], 'prefix normalization failed');
ipv4AssertSame(true, IPv4::contains('10.220.17.0/24', '10.220.17.20', true), 'usable address not contained');
ipv4AssertSame(false, IPv4::contains('10.220.17.0/24', '10.220.17.255', true), 'broadcast treated as usable');
ipv4AssertSame(true, IPv4::overlaps('10.220.0.0/16', '10.220.17.0/24'), 'overlap not detected');
ipv4AssertSame('10.254.0.3', IPv4::firstUsableAddress('10.254.0.0/29', ['10.254.0.2'], ['10.254.0.1']), 'address allocation mismatch');
ipv4AssertSame('10.220.18.0/24', IPv4::firstSubnet('10.220.16.0/22', 24, ['10.220.16.0/24', '10.220.17.0/24']), 'subnet allocation mismatch');
ipv4AssertThrows(static fn () => IPv4::firstUsableAddress('10.0.0.0/30', ['10.0.0.1', '10.0.0.2']), 'exhausted address pool did not fail');
ipv4AssertThrows(static fn () => IPv4::parsePrefix('2001:db8::/64'), 'IPv6 prefix unexpectedly accepted');

fwrite(STDOUT, "ipv4_test: ok\n");
