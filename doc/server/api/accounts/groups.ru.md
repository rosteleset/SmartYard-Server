# `/api/accounts/groups` — список групп

Реализация: `server/api/accounts/groups.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Endpoint доступен только если существует backend `groups` (`loadBackend("groups")`).

## GET `/api/accounts/groups`

Возвращает список групп.

### Ответы

- **Успех 200**: `{"groups":[ ... ]}`
- **Ошибка 404**: `{"error":"notFound"}`

