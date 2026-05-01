# `custom` API (`server/api/custom/`)

## Purpose

Project-specific API hook (`custom.php`).

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/custom`) |
|------|-------------------------------------|
| `custom.php` | `/custom` |

See also the [API index](../README.md) and [`api.php`](../api.md).