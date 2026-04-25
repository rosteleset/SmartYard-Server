# `server/api/authorization/methods.php`

## Маршрут

- **Метод**: `GET`
- **Путь**: `/api/authorization/methods`

## Назначение

Возвращает список API-методов, доступных на сервере (как они проиндексированы в `core_api_methods` после reindex).

## Входные параметры

- `all` (boolean-ish) — передаётся в метод backend’а `methods($_all = true)`.
  - при `all=true`: вернуть все методы
  - при `all=false`: отфильтровать “специальные” методы (common, backend-driven, permissions_same)

## Реализация

Вызывает:

- `authorization->methods($all)`

При успехе возвращает данные под ключом `"methods"`.

## Права / индексация

`index()` помечает endpoint как `#common` (см. `server/utils/reindex.php`).

