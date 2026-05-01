# Backend `sip`

## Назначение

SIP-телефония: поиск сервера/параметров по абоненту, STUN для расширения.

## Код

- **Базовый класс**: `server/backends/sip/sip.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.sip`.

## Основные методы (контракт)

`server`, `stun`.

## Кто использует

`asterisk.php`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

