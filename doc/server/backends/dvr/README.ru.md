# Backend `dvr`

## Назначение

Доступ к DVR: сервера, токены, URL архива и скриншотов для камеры.

## Код

- **Базовый класс**: `server/backends/dvr/dvr.php`.
- **Варианты**: `internal`, `custom`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.dvr`.

## Основные методы (контракт)

`getDVRServerForCam`, `getDVRTokenForCam`, `getDVRStreamURLForCam`, `getDVRServers`, `getUrlOfRecord`, `getUrlOfScreenshot`.

## Кто использует

`asterisk.php`, `plog`, клиенты архива.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

