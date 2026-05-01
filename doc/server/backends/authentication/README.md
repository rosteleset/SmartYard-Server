# `authentication` backend

## Purpose

User authentication: password checks, Redis-backed tokens, logout, session handling. The base class implements `login()` on top of `checkAuth()`.

## Code

- **Base class**: `server/backends/authentication/authentication.php`.
- **Variants**: `internal`, `external`.

## Configuration

Key in `server/config/config.json`: `backends.authentication` section (TTL, max tokens, 2FA, etc.).

## Main API (contract)

Contract: `checkAuth(...)`; see file for inherited login/token helpers.

## Callers

Accounts API (`/api/authentication/*`), web/mobile entrypoints.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

