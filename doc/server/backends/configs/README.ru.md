# Backend `configs`

## Назначение

Справочники оборудования и ПО для UI/интеграций: модели домофонов, камер, CMS.

## Код

- **Базовый класс**: `server/backends/configs/configs.php`.
- **Варианты**: `json`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.configs`.

## Основные методы (контракт)

`getDomophonesModels`, `getCamerasModels`, `getCMSes`.

## Кто использует

`asterisk.php`, `households`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

