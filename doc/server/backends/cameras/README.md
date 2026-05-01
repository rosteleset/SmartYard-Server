# `cameras` backend

## Purpose

Camera registry and configuration: CRUD, DVR/FRS hooks, geo/monitoring fields.

## Code

- **Base class**: `server/backends/cameras/cameras.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.cameras`.

## Main API (contract)

`getCameras`, `getCamera`, `addCamera`, `modifyCamera`, `deleteCamera`, etc.

## Callers

Cameras API, `households`, `asterisk.php`, video integrations.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

