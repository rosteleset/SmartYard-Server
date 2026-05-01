# `plog` backend

## Purpose

Access/event log (calls, openings, faces, vehicles); ClickHouse-backed variant.

## Code

- **Base class**: `server/backends/plog/plog.php`.
- **Variants**: `clickhouse`.

## Configuration

Key in `server/config/config.json`: `backends.plog`.

## Main API (contract)

`getEventsDays`, `getDetailEventsByDay`, UUID lookups — see file.

## Callers

Log APIs, mobile, `households`/`frs`/`dvr`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

