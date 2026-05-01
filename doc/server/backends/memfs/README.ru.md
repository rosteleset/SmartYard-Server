# Backend `memfs`

## Назначение

Хранение небольших бинарных объектов в Redis по UUID (контент в памяти).

## Код

- **Базовый класс**: `server/backends/memfs/memfs.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.memfs`.

## Основные методы (контракт)

`putFile`, `getFile`.

## Кто использует

`asterisk.php`, `households` (быстрые артефакты).

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

