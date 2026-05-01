# Backend `accounting`

## Назначение

Учёт и журналирование обращений к API: запись событий аудита и произвольных syslog-сообщений, выборка по запросу.

## Код

- **Базовый класс**: `server/backends/accounting/accounting.php` — `backends\accounting`.
- **Варианты**: `none`, `syslog`, `clickhouse` — см. подпапки вариантов.

## Конфигурация

Ключ в `server/config/config.json`: `backends.accounting.backend`.

## Основные методы (контракт)

`log($params, $code)`, `raw($ip, $unit, $msg)`, `get($query)`.

## Кто использует

`server/utils/debug.php` и другие места, где нужно зафиксировать вызов API.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

