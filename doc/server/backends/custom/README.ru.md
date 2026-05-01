# Backend `custom`

## Назначение

Расширяемый HTTP-подобный контракт для кастомных сценариев: `GET`/`POST`/`PUT`/`DELETE` с параметрами.

## Код

- **Базовый класс**: `server/backends/custom/custom.php`.
- **Варианты**: `lanta` и др.

## Конфигурация

Ключ в `server/config/config.json`: `backends.custom`.

## Основные методы (контракт)

`GET`, `POST`, `PUT`, `DELETE` (abstract).

## Кто использует

`tt` backend, mobile restore и др. точки, где нужен project-specific hook.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

