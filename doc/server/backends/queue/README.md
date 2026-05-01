# `queue` backend

## Purpose

Deferred work queue, object change notifications, device autoconfiguration.

## Code

- **Base class**: `server/backends/queue/queue.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.queue`.

## Main API (contract)

`getTasks`, `changed`, `autoconfigureDevices`, `wait`.

## Callers

`households` asynchronous pipelines.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

