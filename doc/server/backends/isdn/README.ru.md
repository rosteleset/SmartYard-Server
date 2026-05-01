# Backend `isdn`

## Назначение

Телефония и подтверждение номеров: отправка кода, входящие вызовы, push; варианты `lanta` / `bundle`.

## Код

- **Базовый класс**: `server/backends/isdn/isdn.php`.
- **Варианты**: `lanta`, `bundle`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.isdn`.

## Основные методы (контракт)

`sendCode`, `confirmNumbers`, `checkIncoming`, `push`.

## Кто использует

`mobile/user/*`, `asterisk.php`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

