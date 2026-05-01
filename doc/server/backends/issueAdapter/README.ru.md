# Backend `issueAdapter`

## Назначение

Адаптер внешней системы заявок: создание, список, комментарии, действия.

## Код

- **Базовый класс**: `server/backends/issueAdapter/issueAdapter.php`.
- **Варианты**: `teledom`, `lanta`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.issueAdapter`.

## Основные методы (контракт)

`createIssue`, `listConnectIssues`, `commentIssue`, `actionIssue`.

## Кто использует

`mobile/issues/listConnect*.php`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

