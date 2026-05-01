# Server overview

RBT server is implemented as a set of **mostly vanilla PHP scripts** with **minimal dependencies**.
There is no framework “core” that you must learn first — the codebase is intentionally straightforward and explicit.

## What “server” means in this repository

- **Entrypoints** live in the root of `server/` (e.g. `frontend.php`, `mobile.php`, `cli.php`, `asterisk.php`, etc.).
- **API implementation** is organized under `server/api/`.
- **Backends** provide pluggable implementations for domains/services (`server/backends/`).
- **Utilities** and shared helpers live in `server/utils/`.

## Main characteristics (project pillars)

- **Vanilla PHP**: procedural style is common; classes are used where it makes sense, but the project avoids heavy frameworks.
- **Minimal dependencies**: Composer is used, but intentionally kept small.

## Next docs

- [Entrypoints](./entrypoints/README.md)
- [API implementation (section catalog)](./api/README.md)
- [Backends](./backends/README.md)
- [Utilities](./utils/README.md)

