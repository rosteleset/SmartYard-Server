# `notes` backend

## Purpose

Per-user notes for the web UI.

## Code

- **Base class**: `server/backends/notes/notes.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.notes`.

## Main API (contract)

`getNotes`, `addNote`, `deleteNote`, `reorder`.

## Callers

`server/api/notes/*`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

