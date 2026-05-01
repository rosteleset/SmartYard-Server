# `houses` API (`server/api/houses/`)

## Purpose

Houses, entrances, flats, domophones, CMS, house cameras, search, autoconfigure.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/houses`) |
|------|-------------------------------------|
| `autoconfigure.php` | `/autoconfigure` |
| `cameras.php` | `/cameras` |
| `cms.php` | `/cms` |
| `customFields.php` | `/customFields` |
| `customFieldsConfiguration.php` | `/customFieldsConfiguration` |
| `domophone.php` | `/domophone` |
| `domophones.php` | `/domophones` |
| `entrance.php` | `/entrance` |
| `flat.php` | `/flat` |
| `flats.php` | `/flats` |
| `house.php` | `/house` |
| `leaf.php` | `/leaf` |
| `path.php` | `/path` |
| `search.php` | `/search` |
| `sharedEntrances.php` | `/sharedEntrances` |
| `watch.php` | `/watch` |

See also the [API index](../README.md) and [`api.php`](../api.md).