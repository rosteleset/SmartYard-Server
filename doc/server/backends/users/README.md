# `users` backend

## Purpose

Operator user accounts: CRUD, passwords, groups authorization hooks; constructor wires ClickHouse helper.

## Code

- **Base class**: `server/backends/users/users.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.users`.

## Main API (contract)

`getUsers`, `getUser`, `getUidByLogin`, `addUser`, `setPassword`, etc.

## Callers

Core `backend` uid resolution, authentication, Accounts API, TT.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

