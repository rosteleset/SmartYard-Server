# `accounting` backend

## Purpose

Audit-style logging of API usage: structured log entries, raw syslog-like messages, and queries.

## Code

- **Base class**: `server/backends/accounting/accounting.php` — `backends\accounting`.
- **Variants**: `none`, `syslog`, `clickhouse` (see variant folders).

## Configuration

Key in `server/config/config.json`: `backends.accounting.backend`.

## Main API (contract)

`log($params, $code)`, `raw($ip, $unit, $msg)`, `get($query)`.

## Callers

`server/utils/debug.php` and other API instrumentation.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

