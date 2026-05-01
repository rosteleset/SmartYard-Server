# `addresses` API (`server/api/addresses/`)

## Purpose

Address hierarchy (region → … → house), search, favorites.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/addresses`) |
|------|-------------------------------------|
| `addresses.php` | `/addresses` |
| `area.php` | `/area` |
| `city.php` | `/city` |
| `favorites.php` | `/favorites` |
| `house.php` | `/house` |
| `region.php` | `/region` |
| `search.php` | `/search` |
| `settlement.php` | `/settlement` |
| `street.php` | `/street` |

See also the [API index](../README.md) and [`api.php`](../api.md).

---

## Detailed pages

- [`/api/addresses/addresses` — address hierarchy lookup](./addresses.md)
- [`/api/addresses/region` — region CRUD](./region.md)
- [`/api/addresses/area` — area CRUD](./area.md)
- [`/api/addresses/city` — city CRUD](./city.md)
- [`/api/addresses/settlement` — settlement CRUD](./settlement.md)
- [`/api/addresses/street` — street CRUD](./street.md)
- [`/api/addresses/house` — house CRUD](./house.md)
- [`/api/addresses/search` — full-text address search](./search.md)
- [`/api/addresses/favorites` — favorites](./favorites.md)

# Addresses (`addresses/*`)

This section documents the addresses-related API endpoints implemented under `server/api/addresses/*`.

## Auth and permissions

- All endpoints require `Authorization: Bearer <token>`.
- Requests are routed by `server/frontend.php` and allowed/denied via `authorization->allow($params)`.
- Many write endpoints (and `search`) explicitly reuse the permission model of `/api/addresses/house` via `#same(addresses,house,...)`.

## Index

- [`/api/addresses/addresses` — hierarchical address lists](./addresses.md)
- [`/api/addresses/search` — address search](./search.md)
- [`/api/addresses/house` — house CRUD (+ magic create)](./house.md)
- [`/api/addresses/region` — region CRUD](./region.md)
- [`/api/addresses/area` — area CRUD](./area.md)
- [`/api/addresses/city` — city CRUD](./city.md)
- [`/api/addresses/settlement` — settlement CRUD](./settlement.md)
- [`/api/addresses/street` — street CRUD](./street.md)
- [`/api/addresses/favorites` — favorites CRUD](./favorites.md)