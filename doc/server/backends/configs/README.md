# `configs` backend

## Purpose

Reference data for hardware/software (domophone models, camera models, CMS types).

## Code

- **Base class**: `server/backends/configs/configs.php`.
- **Variants**: `json`.

## Configuration

Key in `server/config/config.json`: `backends.configs`.

## Main API (contract)

`getDomophonesModels`, `getCamerasModels`, `getCMSes`.

## Callers

`asterisk.php`, `households`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

