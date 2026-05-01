# Backend `cs`

## Назначение

«Электронные таблицы» (csheet): хранение JSON в `files` с метаданными, опционально ячейки в Redis, публикация через MQTT.

## Код

- **Базовый класс**: `server/backends/cs/cs.php` (часть методов уже реализована в базовом классе).
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.cs`.

## Основные методы (контракт)

`getCS`, `putCS`, вспомогательные — см. `cs.php`.

## Кто использует

Соответствующие API csheet.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

