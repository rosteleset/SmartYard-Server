# `/api/accounts/users` — список пользователей

Реализация: `server/api/accounts/users.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: маршрутизируется в `server/frontend.php` через `server/api/<api>/<method>.php` и класс `\api\accounts\users`.
- **Backend’и**:
  - backend `users`: `getUsers(withSessions, withLast)`
- **Хранилище / side effects**:
  - GET 200 ответы могут кешироваться в Redis на уровне `server/frontend.php` (frontend cache).
- **Вызывается из UI**:
  - `client/modules/users/users.js` (список пользователей, иногда с `withSessions`)
  - `client/modules/tt/settings.js` (читает список пользователей)

## GET `/api/accounts/users`

Возвращает список пользователей.

### Query-параметры

- `withSessions` (boolean, опционально): включить данные по сессиям (зависит от backend’а)
- `withLast` (boolean, опционально): включить “последнюю активность” (зависит от backend’а)

### Ответы

- **Успех 200**: `{"users":[ ... ]}`
- **Ошибка 404**: `{"error":"notFound"}`

