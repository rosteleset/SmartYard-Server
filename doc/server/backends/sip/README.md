# `sip` backend

## Purpose

SIP helpers: locate server settings, STUN metadata.

## Code

- **Base class**: `server/backends/sip/sip.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.sip`.

## Main API (contract)

`server`, `stun`.

## Callers

`asterisk.php`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

