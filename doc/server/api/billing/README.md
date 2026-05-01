# `billing` API (`server/api/billing/`)

## Purpose

Billing integration: subscriptions/subscriber data, addresses import.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/billing`) |
|------|-------------------------------------|
| `addresses.php` | `/addresses` |
| `subscriptions.php` | `/subscriptions` |

See also the [API index](../README.md) and [`api.php`](../api.md).

---

## Detailed pages

- [`/api/billing/addresses` — import address hierarchy](./addresses.md)
- [`/api/billing/subscriptions` — sync contracts auto-blocking](./subscriptions.md)