# `wg` backend

## Purpose

WireGuard client configuration per login and group.

## Code

- **Base class**: `server/backends/wg/wg.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.wg`.

## Main API (contract)

`clientConfig($login, $group)`.

## Callers

`users/internal` VPN-related flows.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

