# Backend `systemInfo`

## Назначение

Сводка о состоянии сервера/окружения для диагностики UI.

## Код

- **Базовый класс**: `server/backends/systemInfo/systemInfo.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.systemInfo`.

## Основные методы (контракт)

`systemInfo()`.

## Кто использует

Системные/диагностические API.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

