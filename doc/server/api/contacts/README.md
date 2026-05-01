# `contacts` API (`server/api/contacts/`)

## Purpose

Contacts directory: list and single contact.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/contacts`) |
|------|-------------------------------------|
| `contact.php` | `/contact` |
| `contacts.php` | `/contacts` |

See also the [API index](../README.md) and [`api.php`](../api.md).