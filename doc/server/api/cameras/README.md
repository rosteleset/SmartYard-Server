# `cameras` API (`server/api/cameras/`)

## Purpose

Camera registry, single camera, camshot.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/cameras`) |
|------|-------------------------------------|
| `camera.php` | `/camera` |
| `cameras.php` | `/cameras` |
| `camshot.php` | `/camshot` |

See also the [API index](../README.md) and [`api.php`](../api.md).

---

## Detailed pages

- [`/api/cameras/cameras` — list cameras + models + FRS servers + tree](./cameras.md)
- [`/api/cameras/camera` — camera CRUD](./camera.md)
- [`/api/cameras/camshot` — snapshot](./camshot.md)