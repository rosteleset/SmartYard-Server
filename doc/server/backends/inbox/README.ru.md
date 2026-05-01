# Backend `inbox`

## Назначение

Входящие сообщения абонентам мобильного приложения: отправка, списки, статусы прочитано/доставлено.

## Код

- **Базовый класс**: `server/backends/inbox/inbox.php`.
- **Варианты**: `clickhouse`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.inbox`.

## Основные методы (контракт)

`sendMessage`, `getMessages`, `markMessageAsReaded`, `markMessageAsDelivered`, `unreaded`, `undelivered`.

## Кто использует

Mobile user flows, `dvrExports` после выгрузки видео.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

