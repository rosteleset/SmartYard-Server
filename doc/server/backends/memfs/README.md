# `memfs` backend

## Purpose

Small binary blobs in Redis keyed by UUID.

## Code

- **Base class**: `server/backends/memfs/memfs.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.memfs`.

## Main API (contract)

`putFile`, `getFile`.

## Callers

`asterisk.php`, `households`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

