# `mkb` backend

## Purpose

Kanban boards/cards API.

## Code

- **Base class**: `server/backends/mkb/mkb.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.mkb`.

## Main API (contract)

`getDesks`, `upsertDesk`, `deleteDesk`, `getCards`, `countCards`, `upsertCard`, `deleteCard`, `transferCard`.

## Callers

`server/api/mkb/*`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

