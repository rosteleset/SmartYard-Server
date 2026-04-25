# `server/api/authorization/available.php`

## Маршрут

- **Метод**: `GET`
- **Путь**: `/api/authorization/available`

## Назначение

Возвращает список API-методов, доступных текущему пользователю.

## Реализация

Делегирует в backend `authorization`:

- `authorization->allowedMethods($uid)`

и возвращает успешный payload под ключом `"available"`.

## Права / индексация

`index()` помечает endpoint как `#common`, поэтому он попадает в набор **общих методов** при `reindex()` (см. `server/utils/reindex.php`).

