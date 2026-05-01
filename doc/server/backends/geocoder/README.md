# `geocoder` backend

## Purpose

Address suggestions / geo search for UI and imports.

## Code

- **Base class**: `server/backends/geocoder/geocoder.php`.
- **Variants**: `dadata`.

## Configuration

Key in `server/config/config.json`: `backends.geocoder`.

## Main API (contract)

`suggestions($search)`.

## Callers

`issueAdapter/teledom`, address forms.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

