# Server API (`server/api/`)

Реализация HTTP API для Web UI и связанных клиентов: классы в `server/api/<module>/`, базовый контракт в [`api.php`](./api.ru.md). Диспетчеризация — [`server/frontend.php`](../../entrypoints/frontend.ru.md) (путь `/api/<module>/<endpoint>`).

## Общие материалы

- [Базовый класс `api.php`](./api.ru.md)

## Каталог разделов API

| Раздел | Документация |
|--------|----------------|
| `accounts` | [README](./accounts/README.ru.md) |
| `addresses` | [README](./addresses/README.ru.md) |
| `authentication` | [README](./authentication/README.ru.md) |
| `authorization` | [README](./authorization/README.ru.md) |
| `billing` | [README](./billing/README.ru.md) |
| `cameras` | [README](./cameras/README.ru.md) |
| `cdr` | [README](./cdr/README.ru.md) |
| `companies` | [README](./companies/README.ru.md) |
| `configs` | [README](./configs/README.ru.md) |
| `contacts` | [README](./contacts/README.ru.md) |
| `cs` | [README](./cs/README.ru.md) |
| `custom` | [README](./custom/README.ru.md) |
| `files` | [README](./files/README.ru.md) |
| `geo` | [README](./geo/README.ru.md) |
| `houses` | [README](./houses/README.ru.md) |
| `inbox` | [README](./inbox/README.ru.md) |
| `mkb` | [README](./mkb/README.ru.md) |
| `mqtt` | [README](./mqtt/README.ru.md) |
| `notes` | [README](./notes/README.ru.md) |
| `providers` | [README](./providers/README.ru.md) |
| `queues` | [README](./queues/README.ru.md) |
| `server` | [README](./server/README.ru.md) |
| `subscribers` | [README](./subscribers/README.ru.md) |
| `tt` | [README](./tt/README.ru.md) |
| `ud363` | [README](./ud363/README.ru.md) |
| `user` | [README](./user/README.ru.md) |

### Примечание

У части разделов есть отдельные страницы по endpoint’ам — см. блок «Подробная документация» в соответствующем `README`.
