# Backend `monitoring`

## Назначение

Проверка доступности устройств для UI мониторинга и автоконфигурации.

## Код

- **Базовый класс**: `server/backends/monitoring/monitoring.php`.
- **Варианты**: `simple`, `zabbix`, `prometheus`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.monitoring`.

## Основные методы (контракт)

`deviceStatus`, `devicesStatus`, `configureMonitoring`.

## Кто использует

`households` при статусах оборудования.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

