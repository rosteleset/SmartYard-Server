# `tt` backend

## Purpose

Ticketing / TT subsystem with Lua workflows, files-backed attachments, users/groups integration.

## Code

- **Base class**: `server/backends/tt/tt.php` (+ `workflow.php`).
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.tt`.

## Main API (contract)

Large surface — see `tt.php` and `server/api/tt/*`.

## Callers

TT API routes.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

