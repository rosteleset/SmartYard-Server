# `isdn` backend

## Purpose

Telephony hooks: OTP/codes, inbound handling, push; `lanta` / `bundle` variants.

## Code

- **Base class**: `server/backends/isdn/isdn.php`.
- **Variants**: `lanta`, `bundle`.

## Configuration

Key in `server/config/config.json`: `backends.isdn`.

## Main API (contract)

`sendCode`, `confirmNumbers`, `checkIncoming`, `push`.

## Callers

`mobile/user/*`, `asterisk.php`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

