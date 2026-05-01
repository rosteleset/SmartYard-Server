# Backend `mqtt`

## Назначение

Публикация сообщений через локальный MQTT-agent (HTTP к `backends.mqtt.agent`). Базовый класс даёт `broadcast()` и `getConfig()`.

## Код

- **Базовый класс**: `server/backends/mqtt/mqtt.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: секция `backends.mqtt` (`agent`, настройки брокера).

## Основные методы (контракт)

`broadcast`, `getConfig`; см. variant для подписок.

## Кто использует

`cs`, другие real-time сценарии.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

