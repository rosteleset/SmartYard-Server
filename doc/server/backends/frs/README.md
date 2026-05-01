# `frs` backend

## Purpose

Face/event recognition service integration; see `M_*` / `P_*` constants in the base class.

## Code

- **Base class**: `server/backends/frs/frs.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.frs`.

## Main API (contract)

Large API surface for streams, faces, callbacks.

## Callers

`plog`, video analytics.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

