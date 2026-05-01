# Backends (`server/backends/`)

This section documents the backend subsystem: pluggable domain services configured under `server/config/config.json` → `"backends"` and loaded via `loadBackend()` (see [`loader.php`](../utils/loader.md) and the [`backend.php` base class](./backend.md)).

## Shared docs

- [Base backend class (`backend.php`)](./backend.md)
- [Backend CLI extensions](./cli-extensions.md) — which backends define `cliUsage()` / `cli()`
- [Custom backend variant pattern](../utils/loader.md#custom-variant-project-customization)
- [Accounts-related backends overview](./accounts/README.md) — `users`, `groups`, `authentication`, `authorization`

## Backend catalog

Each row links to a short overview (purpose, variants, main methods).

| Backend | Documentation |
|--------|-----------------|
| `accounting` | [README](./accounting/README.md) |
| `addresses` | [README](./addresses/README.md) |
| `authorization` | [README](./authorization/README.md) |
| `authentication` | [README](./authentication/README.md) |
| `billing` | [README](./billing/README.md) |
| `cameras` | [README](./cameras/README.md) |
| `cdr` | [README](./cdr/README.md) |
| `companies` | [README](./companies/README.md) |
| `configs` | [README](./configs/README.md) |
| `contacts` | [README](./contacts/README.md) |
| `cs` | [README](./cs/README.md) |
| `custom` | [README](./custom/README.md) |
| `customFields` | [README](./customFields/README.md) |
| `dvr` | [README](./dvr/README.md) |
| `dvrExports` | [README](./dvrExports/README.md) |
| `extfs` | [README](./extfs/README.md) |
| `files` | [README](./files/README.md) |
| `frs` | [README](./frs/README.md) |
| `geocoder` | [README](./geocoder/README.md) |
| `groups` | [README](./groups/README.md) |
| `households` | [README](./households/README.md) |
| `inbox` | [README](./inbox/README.md) |
| `isdn` | [README](./isdn/README.md) |
| `issueAdapter` | [README](./issueAdapter/README.md) |
| `memfs` | [README](./memfs/README.md) |
| `mkb` | [README](./mkb/README.md) |
| `monitoring` | [README](./monitoring/README.md) |
| `mqtt` | [README](./mqtt/README.md) |
| `notes` | [README](./notes/README.md) |
| `plog` | [README](./plog/README.md) |
| `processes` | [README](./processes/README.md) |
| `providers` | [README](./providers/README.md) |
| `queue` | [README](./queue/README.md) |
| `sip` | [README](./sip/README.md) |
| `systemInfo` | [README](./systemInfo/README.md) |
| `tmpfs` | [README](./tmpfs/README.md) |
| `tt` | [README](./tt/README.md) |
| `ud363` | [README](./ud363/README.md) |
| `users` | [README](./users/README.md) |
| `wg` | [README](./wg/README.md) |
