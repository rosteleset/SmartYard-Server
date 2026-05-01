# `inbox` backend

## Purpose

Subscriber inbox for the mobile app: send, list, read/delivery markers.

## Code

- **Base class**: `server/backends/inbox/inbox.php`.
- **Variants**: `clickhouse`.

## Configuration

Key in `server/config/config.json`: `backends.inbox`.

## Main API (contract)

`sendMessage`, `getMessages`, `markMessageAsReaded`, `markMessageAsDelivered`, `unreaded`, `undelivered`.

## Callers

Mobile endpoints; `dvrExports` notifications.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

