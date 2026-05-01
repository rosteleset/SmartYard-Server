# Backend `geocoder`

## Назначение

Подсказки адресов и гео-поиск для UI и импортов.

## Код

- **Базовый класс**: `server/backends/geocoder/geocoder.php`.
- **Варианты**: `dadata`.

## Конфигурация

Ключ в `server/config/config.json`: секция `backends.geocoder` (токен DaData и пр.).

## Основные методы (контракт)

`suggestions($search)`.

## Кто использует

`issueAdapter/teledom`, формы адресов.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

