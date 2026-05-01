# `cs` backend

## Purpose

Spreadsheet-like "csheet" documents stored in `files` with metadata; optional Redis cells; MQTT publish.

## Code

- **Base class**: `server/backends/cs/cs.php` (partial default implementations).
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.cs`.

## Main API (contract)

`getCS`, `putCS`, helpers — see `cs.php`.

## Callers

CSheet-related API routes.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

