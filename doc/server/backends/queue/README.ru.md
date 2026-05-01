# Backend `queue`

## Назначение

Очередь отложенных задач и реакция на изменения объектов (`changed`), автонастройка устройств.

## Код

- **Базовый класс**: `server/backends/queue/queue.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.queue`.

## Основные методы (контракт)

`getTasks`, `changed`, `autoconfigureDevices`, `wait`.

## Кто использует

`households` (массовые асинхронные операции).

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

