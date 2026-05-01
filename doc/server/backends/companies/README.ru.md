# Backend `companies`

## Назначение

Управление организациями (подрядчики/УК): список, карточка, CRUD.

## Код

- **Базовый класс**: `server/backends/companies/companies.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.companies`.

## Основные методы (контракт)

`getCompanies`, `getCompany`, `addCompany`, `modifyCompany`, `deleteCompany`.

## Кто использует

`households` и API компаний.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

