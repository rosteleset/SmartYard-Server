# `extfs` backend

## Purpose

External binary storage by UUID (streams); used alongside `tmpfs` from `files/mongo`.

## Code

- **Base class**: `server/backends/extfs/extfs.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.extfs`.

## Main API (contract)

`putFile`, `getFile`, `deleteFile`.

## Callers

`files/mongo`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

