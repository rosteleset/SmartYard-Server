# `tmpfs` backend

## Purpose

Temporary on-disk storage by UUID (streams); used inside the `files` pipeline.

## Code

- **Base class**: `server/backends/tmpfs/tmpfs.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.tmpfs`.

## Main API (contract)

`putFile`, `getFile`, `deleteFile`.

## Callers

`files/mongo`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

