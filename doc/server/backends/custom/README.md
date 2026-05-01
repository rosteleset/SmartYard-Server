# `custom` backend

## Purpose

Project-specific hooks behind a small HTTP-like contract (`GET`/`POST`/`PUT`/`DELETE`).

## Code

- **Base class**: `server/backends/custom/custom.php`.
- **Variants**: `lanta`, etc.

## Configuration

Key in `server/config/config.json`: `backends.custom`.

## Main API (contract)

`GET`, `POST`, `PUT`, `DELETE` (abstract).

## Callers

`tt` backend, mobile flows, optional integrations.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

