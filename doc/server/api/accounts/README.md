# `accounts` API (`server/api/accounts/`)

## Purpose

Operator accounts and groups: users, groups, membership. Password recovery may use a non-`/api/...` path — see detailed pages below.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/accounts`) |
|------|-------------------------------------|
| `group.php` | `/group` |
| `groupUsers.php` | `/groupUsers` |
| `groups.php` | `/groups` |
| `user.php` | `/user` |
| `users.php` | `/users` |

See also the [API index](../README.md) and [`api.php`](../api.md).

---

## Detailed pages

- [`/api/accounts/user` — user CRUD](./user.md)
- [`/api/accounts/users` — users list](./users.md)
- [`/api/accounts/group` — group CRUD](./group.md)
- [`/api/accounts/groups` — groups list](./groups.md)
- [`/api/accounts/groupUsers` — group membership](./groupUsers.md)
- [`/accounts/forgot` — password reset (public)](./forgot.md)