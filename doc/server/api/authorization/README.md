# `authorization` API (`server/api/authorization/`)

## Purpose

API method rights matrix, allowed methods, bulk rights.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/authorization`) |
|------|-------------------------------------|
| `available.php` | `/available` |
| `methods.php` | `/methods` |
| `rights.php` | `/rights` |

See also the [API index](../README.md) and [`api.php`](../api.md).

---

## Detailed pages

- [`available.php`](./available.md) — `GET /api/authorization/available`
- [`methods.php`](./methods.md) — `GET /api/authorization/methods`
- [`rights.php`](./rights.md) — `GET/POST /api/authorization/rights`