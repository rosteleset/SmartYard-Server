# Backend `cameras`

## Назначение

Справочник и конфигурация камер: CRUD, привязка к DVR/FRS, геометрия, мониторинг.

## Код

- **Базовый класс**: `server/backends/cameras/cameras.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.cameras`.

## Основные методы (контракт)

`getCameras`, `getCamera`, `addCamera`, `modifyCamera`, `deleteCamera` и др.

## Кто использует

Cameras API, `households`, `asterisk.php`, интеграции видео.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

