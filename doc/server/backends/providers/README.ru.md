# Backend `providers`

## Назначение

Справочник внешних провайдеров (SMS и др.): JSON-конфиг, CRUD записей.

## Код

- **Базовый класс**: `server/backends/providers/providers.php`.
- **Варианты**: `lanta`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.providers`.

## Основные методы (контракт)

`getJson`, `putJson`, `getProviders`, `addProvider`, `modifyProvider`, `deleteProvider`.

## Кто использует

Админские API провайдеров.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

