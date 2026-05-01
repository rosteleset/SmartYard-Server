# `systemInfo` backend

## Purpose

Server/environment diagnostics snapshot for admin UI.

## Code

- **Base class**: `server/backends/systemInfo/systemInfo.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.systemInfo`.

## Main API (contract)

`systemInfo()`.

## Callers

Diagnostics endpoints.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

