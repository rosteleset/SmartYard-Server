# `mqtt` backend

## Purpose

Publish via a local MQTT agent HTTP endpoint; base helpers `broadcast()` / `getConfig()`.

## Code

- **Base class**: `server/backends/mqtt/mqtt.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.mqtt`.

## Main API (contract)

`broadcast`, `getConfig`.

## Callers

`cs`, realtime integrations.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

