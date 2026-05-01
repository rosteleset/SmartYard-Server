# RBT documentation index

This folder contains project documentation for `rbt`.

## About the project

**RoBoT / RBT** — open IP intercom & video platform (standalone server + SPA). Upstream the codebase is known as **SmartYard-Server**.

- Website: [sesameware.com](https://sesameware.com)
- Upstream notes: [important.md](https://github.com/rosteleset/SmartYard-Server/blob/main/important.md)
- Wiki: [GitHub Wiki](https://github.com/rosteleset/SmartYard-Server/wiki)
- Generated API docs (WiP, upstream): [SERVER API](https://rosteleset.github.io/SmartYard-Server/doc/api/), [MOBILE API](https://rosteleset.github.io/SmartYard-Server/doc/mobile/)
- [Changelog (upstream)](https://github.com/rosteleset/SmartYard-Server/blob/main/changelog.md)

## Index

## Project pillars (what the system stands on)

- **Client**: a **SPA** (single-page application) that talks to the server **only via HTTP API**. There is **no server-side rendering (SSR)** in this project.
- **Server**: a set of mostly **vanilla PHP scripts** with **minimal dependencies** (Composer is used, but intentionally kept small).

## Roadmap (target table of contents)

The items below are the documentation structure we aim to complete. Some pages may not exist yet — the links are the plan.

### Architecture

- [Overview](./architecture.md)
- [Domain glossary](./domain/glossary.md)
- [Data model overview](./domain/data-model.md)

### API

- [Web UI API (frontend)](./api/frontend.md)
- [Mobile API](./api/mobile.md)
- [Billing API](./billing.api.md)
- [API conventions (routing, auth, errors, caching)](./api/conventions.md)

### Client / Customization

- [Client overview (SPA, modules, routing)](./client/overview.md)
- [Client configuration](./client/config.md)
- [Client modules](./client/modules.md)
- [SPA modules (`client/modules`)](./client/spa-modules.md)
- [Customization: customFields](./customFields.md)
- [Customization examples](./examples/client/README.md)

### Server / Utilities

- [Server overview (vanilla PHP, entrypoints)](./server/overview.md)
- [Entrypoints](./server/entrypoints/README.md)
  - [frontend.php (Web UI API gateway)](./server/entrypoints/frontend.md)
  - [mobile.php (Mobile API gateway)](./server/entrypoints/mobile.md)
  - [cli.php (CLI tooling)](./server/entrypoints/cli.md)
  - [asterisk.php (Asterisk integration)](./server/entrypoints/asterisk.md)
  - [internal.php (Internal API gateway)](./server/entrypoints/internal.md)
  - [kamailio.php (Kamailio integration)](./server/entrypoints/kamailio.md)
  - [wh.php (Webhooks)](./server/entrypoints/wh.md)
  - [ud363.php (HTTP upload / XEP-0363 placeholder)](./server/entrypoints/ud363.md)
  - [qr.php (QR endpoint)](./server/entrypoints/qr.md)
  - [test.php (Local testing)](./server/entrypoints/test.md)
- [API implementation (server/api)](./server/api/README.md)
- [Base API class (`server/api/api.php`)](./server/api/api.md)
- [Backends (server/backends)](./server/backends/README.md)
- [Base backend class (`server/backends/backend.php`)](./server/backends/backend.md)
- [`groups` backend](./server/backends/groups/README.md)
- [Utilities (server/utils)](./server/utils/README.md)
  - [PDOExt (PDO helper)](./server/utils/PDOExt.md)
  - [loader.php (dynamic loaders)](./server/utils/loader.md)
- [Auxiliary services (`server/services`)](./server/services/README.md)

### Storage and services

- [PostgreSQL (PDO) integration](./server/storage/postgresql.md)
- [Redis usage](./server/storage/redis.md)
- [MongoDB usage (files, GridFS)](./server/storage/mongodb.md)
- [ClickHouse usage](./server/storage/clickhouse.md)

### Telephony and realtime

- [Asterisk integration](./asterisk/README.md)
- [Kamailio integration](./server/kamailio/README.md)
- [MQTT integration](./server/mqtt/README.md)

### TT (tickets/workflows)

- [TT overview](./tt/README.md)
- [Workflows (Lua)](./tt/workflows.md)
- [Filters and viewers](./tt/filters-and-viewers.md)
- [Examples](./examples/tt/README.md)

### Hardware

- [IS syslog events](./hardware/is/syslog_events.md)
- [QTech syslog events](./hardware/qtech/syslog_events.md)

### Installation and operations

- [Installation index](./install/README.md) (step files live in [`install/`](../install/))
- [Crontabs and scheduled jobs](./server/operations/crontabs.md)
- [Maintenance mode](./server/operations/maintenance.md)
- [Backups and recovery](./server/operations/backups.md)

### Examples

- [Server examples](./examples/server/README.md)
- [Custom server examples](./examples/custom/server/README.md)
- [Custom client examples](./examples/custom/client/README.md)


