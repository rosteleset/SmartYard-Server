# `monitoring` backend

## Purpose

Device reachability for monitoring UI / integration.

## Code

- **Base class**: `server/backends/monitoring/monitoring.php`.
- **Variants**: `simple`, `zabbix`, `prometheus`.

## Configuration

Key in `server/config/config.json`: `backends.monitoring`.

## Main API (contract)

`deviceStatus`, `devicesStatus`, `configureMonitoring`.

## Callers

`households` equipment status paths.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

