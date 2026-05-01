# Backend `mkb`

## Назначение

Kanban-доски и карточки (MKB): списки, upsert, перенос между пользователями.

## Код

- **Базовый класс**: `server/backends/mkb/mkb.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.mkb`.

## Основные методы (контракт)

`getDesks`, `upsertDesk`, `deleteDesk`, `getCards`, `countCards`, `upsertCard`, `deleteCard`, `transferCard`.

## Кто использует

`server/api/mkb/*`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

