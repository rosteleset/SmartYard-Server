# Backend `extfs`

## Назначение

Внешнее файловое хранилище по UUID (потоки): совместно с `tmpfs` используется в слое `files/mongo`.

## Код

- **Базовый класс**: `server/backends/extfs/extfs.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.extfs`.

## Основные методы (контракт)

`putFile`, `getFile`, `deleteFile`.

## Кто использует

`files/mongo`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

