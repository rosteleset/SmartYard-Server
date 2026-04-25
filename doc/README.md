# RBT documentation index

This folder contains project documentation for `rbt`.

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
- [Customization: customFields](./customFields.md)
- [Customization examples](./examples/client/README.md)

### Server / Utilities

- [Server overview (vanilla PHP, entrypoints)](./server/overview.md)
- [Entrypoints](./server/entrypoints/README.md)
  - [frontend.php (Web UI API gateway)](./server/entrypoints/frontend.md)
  - [mobile.php (Mobile API gateway)](./server/entrypoints/mobile.md)
  - [cli.php (CLI tooling)](./server/entrypoints/cli.md)
- [API implementation (server/api)](./server/api/README.md)
- [Backends (server/backends)](./server/backends/README.md)
- [Utilities (server/utils)](./server/utils/README.md)
  - [PDOExt (PDO helper)](./server/utils/PDOExt.md)

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

- [Installation guide](../install/README.md)
- [Crontabs and scheduled jobs](./server/operations/crontabs.md)
- [Maintenance mode](./server/operations/maintenance.md)
- [Backups and recovery](./server/operations/backups.md)

### Examples

- [Server examples](./examples/server/README.md)
- [Custom server examples](./examples/custom/server/README.md)
- [Custom client examples](./examples/custom/client/README.md)


