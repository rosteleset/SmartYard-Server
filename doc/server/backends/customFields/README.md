# `customFields` backend

## Purpose

Entity custom fields keyed by `applyTo` and id: CRUD values, search, field definitions.

## Code

- **Base class**: `server/backends/customFields/customFields.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.customFields`.

## Main API (contract)

`getValues`, `modifyValues`, `deleteValues`, `searchByValue`, `getFields`.

## Callers

`households`, `billing`, houses custom-fields API.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

