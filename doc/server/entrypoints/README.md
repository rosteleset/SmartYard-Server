# Server entrypoints

This section documents **server entrypoints** — scripts located in the root of `server/` that act as HTTP gateways or CLI tools.

## Entrypoints index

- [frontend.php](./frontend.md) — Web UI API gateway (HTTP).
- [mobile.php](./mobile.md) — Mobile API gateway (HTTP).
- [cli.php](./cli.md) — CLI tooling (installation, maintenance, cron setup, etc.).
- [asterisk.php](./asterisk.md) — Asterisk integration endpoint (HTTP).
- [internal.php](./internal.md) — Internal API gateway (HTTP).
- [kamailio.php](./kamailio.md) — Kamailio integration endpoint (HTTP).
- [wh.php](./wh.md) — Webhook gateway (HTTP).
- [ud363.php](./ud363.md) — UD363 integration endpoint (HTTP).
- [qr.php](./qr.md) — QR endpoint (HTTP).
- [test.php](./test.md) — Local development/testing entrypoint.

## Notes

- These entrypoints are intentionally **simple PHP scripts**: they load config, connect to storages (DB/Redis/etc.), and dispatch requests to `server/api/*` and/or backends.
- `test.php` is intended for **local testing** and is not a production interface.

