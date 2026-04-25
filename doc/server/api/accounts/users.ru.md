# `/api/accounts/users` — список пользователей

Реализация: `server/api/accounts/users.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## GET `/api/accounts/users`

Возвращает список пользователей.

### Query-параметры

- `withSessions` (boolean, опционально): включить данные по сессиям (зависит от backend’а)
- `withLast` (boolean, опционально): включить “последнюю активность” (зависит от backend’а)

### Ответы

- **Успех 200**: `{"users":[ ... ]}`
- **Ошибка 404**: `{"error":"notFound"}`

