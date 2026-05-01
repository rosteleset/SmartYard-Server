# Backend `customFields`

## Назначение

Произвольные поля сущностей (`applyTo` + id): чтение/запись/поиск значений, схема полей.

## Код

- **Базовый класс**: `server/backends/customFields/customFields.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.customFields`.

## Основные методы (контракт)

`getValues`, `modifyValues`, `deleteValues`, `searchByValue`, `getFields`.

## Кто использует

`households`, `billing`, API custom fields для домов.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

