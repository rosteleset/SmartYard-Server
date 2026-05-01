# Backend `wg`

## Назначение

Конфигурация WireGuard для клиента по логину и группе.

## Код

- **Базовый класс**: `server/backends/wg/wg.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.wg`.

## Основные методы (контракт)

`clientConfig($login, $group)`.

## Кто использует

`users/internal` при выдаче VPN-профилей.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

