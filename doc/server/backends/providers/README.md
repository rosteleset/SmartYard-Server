# `providers` backend

## Purpose

External provider registry (SMS, etc.) with JSON blob + CRUD.

## Code

- **Base class**: `server/backends/providers/providers.php`.
- **Variants**: `lanta`.

## Configuration

Key in `server/config/config.json`: `backends.providers`.

## Main API (contract)

`getJson`, `putJson`, `getProviders`, `addProvider`, `modifyProvider`, `deleteProvider`.

## Callers

Admin/provider APIs.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

