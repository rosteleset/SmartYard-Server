# `ud363` API (`server/api/ud363/`)

## Purpose

HTTP-facing **stub** for the **slot → upload → download URL** pattern described in **[XEP-0363: HTTP File Upload](https://xmpp.org/extensions/xep-0363.html)**. This is **not** raw XMPP IQ traffic in the browser; the same concepts (filename, size, type, slot, PUT/GET) are mapped onto `/api/ud363/...` calls.

Handlers (`upload`, `download`) are still placeholders (`GET`/`POST` return `true`); inline `@api` comments use XEP vocabulary (**upload slot**, **file part**).

## Mapping to XEP-0363

| XEP idea | Intended API shape |
|----------|-------------------|
| Slot request (`filename`, `size`, optional `content-type`) | `GET /api/ud363/upload` with `name`, `date`, `type`, `size` query params (see `upload.php`) |
| Upload bytes to the issued PUT URL | `POST /api/ud363/upload` with chunk metadata (`slot`, `part`) |
| Fetch download URL | `GET /api/ud363/download` |

For the full protocol sketch (allowed PUT headers, errors, CORS, security), see the **[`ud363.php` entrypoint](../../entrypoints/ud363.md)**.

## Routing (Web UI)

The SPA goes through `server/frontend.php`. **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php` as class **`api\<module>\<endpoint>`** with static HTTP verb methods. Optional override via **`server/api/<module>/custom/<endpoint>.php`**.

Response envelope: [`api.php`](../api.md).

## Endpoint files

| File | Path (under `/api/ud363`) |
|------|-------------------------------------|
| `download.php` | `/download` |
| `upload.php` | `/upload` |

See also the [API index](../README.md) and [`api.php`](../api.md).
