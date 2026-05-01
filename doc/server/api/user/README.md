# `user` API (`server/api/user/`)

## Purpose

Current web user: profile, settings, sudo, avatar.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/user`) |
|------|-------------------------------------|
| `avatar.php` | `/avatar` |
| `notify.php` | `/notify` |
| `personal.php` | `/personal` |
| `settings.php` | `/settings` |
| `sudo.php` | `/sudo` |
| `whoAmI.php` | `/whoAmI` |

See also the [API index](../README.md) and [`api.php`](../api.md).