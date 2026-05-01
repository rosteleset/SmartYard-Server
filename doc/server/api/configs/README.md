# `configs` API (`server/api/configs/`)

## Purpose

Reference lists for domophone/camera models and CMS types.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/configs`) |
|------|-------------------------------------|
| `configs.php` | `/configs` |

See also the [API index](../README.md) and [`api.php`](../api.md).