# `households` backend

## Purpose

Core domain backend for houses, entrances, flats, subscribers, domophones, SIP/ISDN hooks, queues.

## Code

- **Base class**: `server/backends/households/households.php` (large abstract surface).
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.households`.

## Main API (contract)

Many methods — see the base class file.

## Callers

`mobile.php`, `asterisk.php`, `billing`, cameras, queue — central to access control.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

