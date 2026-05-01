# Backend `tmpfs`

## Назначение

Временное файловое хранилище на диске по UUID (потоки); используется слоем `files` перед выгрузкой во внешнее хранилище.

## Код

- **Базовый класс**: `server/backends/tmpfs/tmpfs.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.tmpfs`.

## Основные методы (контракт)

`putFile`, `getFile`, `deleteFile`.

## Кто использует

`files/mongo`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

