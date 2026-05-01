# `subscribers` API (`server/api/subscribers/`)

## Purpose

Subscribers, devices, keys, search, flat cameras.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/subscribers`) |
|------|-------------------------------------|
| `device.php` | `/device` |
| `devices.php` | `/devices` |
| `flatCameras.php` | `/flatCameras` |
| `key.php` | `/key` |
| `keys.php` | `/keys` |
| `search.php` | `/search` |
| `searchFlat.php` | `/searchFlat` |
| `searchRf.php` | `/searchRf` |
| `subscriber.php` | `/subscriber` |
| `subscriberCameras.php` | `/subscriberCameras` |
| `subscribers.php` | `/subscribers` |

See also the [API index](../README.md) and [`api.php`](../api.md).