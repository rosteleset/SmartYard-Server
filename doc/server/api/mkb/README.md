# `mkb` API (`server/api/mkb/`)

## Purpose

Kanban boards/cards (MKB), send, shared desks.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/mkb`) |
|------|-------------------------------------|
| `card.php` | `/card` |
| `cards.php` | `/cards` |
| `desk.php` | `/desk` |
| `desks.php` | `/desks` |
| `otherCards.php` | `/otherCards` |
| `otherDesks.php` | `/otherDesks` |
| `send.php` | `/send` |

See also the [API index](../README.md) and [`api.php`](../api.md).