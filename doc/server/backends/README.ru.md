# Backends (`server/backends/`)

В этом разделе описана подсистема backend’ов: подключаемые реализации доменных сервисов, которые настраиваются в `server/config/config.json` в секции `"backends"` и загружаются через `loadBackend()` (см. [загрузчик `loader.php`](../utils/loader.ru.md), [базовый класс `backend.php`](./backend.ru.md)).

## Общие материалы

- [Базовый класс backend’ов (`backend.php`)](./backend.ru.md)
- [Паттерн custom backend variant](../utils/loader.ru.md#custom-variant-кастомизация-проекта)
- [Backend’и вокруг Accounts (обзор)](./accounts/README.ru.md) — `users`, `groups`, `authentication`, `authorization`

## Каталог backend’ов

Каждый пункт ведёт на краткое описание назначения, вариантов и основных методов.

| Backend | Документация |
|--------|----------------|
| `accounting` | [README](./accounting/README.ru.md) |
| `addresses` | [README](./addresses/README.ru.md) |
| `authorization` | [README](./authorization/README.ru.md) |
| `authentication` | [README](./authentication/README.ru.md) |
| `billing` | [README](./billing/README.ru.md) |
| `cameras` | [README](./cameras/README.ru.md) |
| `cdr` | [README](./cdr/README.ru.md) |
| `companies` | [README](./companies/README.ru.md) |
| `configs` | [README](./configs/README.ru.md) |
| `contacts` | [README](./contacts/README.ru.md) |
| `cs` | [README](./cs/README.ru.md) |
| `custom` | [README](./custom/README.ru.md) |
| `customFields` | [README](./customFields/README.ru.md) |
| `dvr` | [README](./dvr/README.ru.md) |
| `dvrExports` | [README](./dvrExports/README.ru.md) |
| `extfs` | [README](./extfs/README.ru.md) |
| `files` | [README](./files/README.ru.md) |
| `frs` | [README](./frs/README.ru.md) |
| `geocoder` | [README](./geocoder/README.ru.md) |
| `groups` | [README](./groups/README.ru.md) |
| `households` | [README](./households/README.ru.md) |
| `inbox` | [README](./inbox/README.ru.md) |
| `isdn` | [README](./isdn/README.ru.md) |
| `issueAdapter` | [README](./issueAdapter/README.ru.md) |
| `memfs` | [README](./memfs/README.ru.md) |
| `mkb` | [README](./mkb/README.ru.md) |
| `monitoring` | [README](./monitoring/README.ru.md) |
| `mqtt` | [README](./mqtt/README.ru.md) |
| `notes` | [README](./notes/README.ru.md) |
| `plog` | [README](./plog/README.ru.md) |
| `processes` | [README](./processes/README.ru.md) |
| `providers` | [README](./providers/README.ru.md) |
| `queue` | [README](./queue/README.ru.md) |
| `sip` | [README](./sip/README.ru.md) |
| `systemInfo` | [README](./systemInfo/README.ru.md) |
| `tmpfs` | [README](./tmpfs/README.ru.md) |
| `tt` | [README](./tt/README.ru.md) |
| `ud363` | [README](./ud363/README.ru.md) |
| `users` | [README](./users/README.ru.md) |
| `wg` | [README](./wg/README.ru.md) |
