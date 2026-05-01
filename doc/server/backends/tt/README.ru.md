# Backend `tt`

## Назначение

Тикеты/задачи (TT): проекты, workflow на Lua, вложения через `files`, интеграция с пользователями/группами и MQTT.

## Код

- **Базовый класс**: `server/backends/tt/tt.php` (+ `workflow.php`).
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.tt`.

## Основные методы (контракт)

Очень большой контракт: workflow, теги, комментарии, вложения — см. файл и `server/api/tt/*`.

## Кто использует

`server/api/tt/*`, внутренние сценарии.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

