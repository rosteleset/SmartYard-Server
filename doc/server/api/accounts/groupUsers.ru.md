# `/api/accounts/groupUsers` — состав группы

Реализация: `server/api/accounts/groupUsers.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Модель прав явно привязана к `/api/accounts/group` через `index()`:
  - `GET /api/accounts/groupUsers/:gid` использует те же права, что и `GET /api/accounts/group/:gid`
  - `PUT /api/accounts/groupUsers/:gid` использует те же права, что и `PUT /api/accounts/group/:gid`

## GET `/api/accounts/groupUsers/:gid`

Возвращает список `uid` участников группы.

- **Параметр**: `gid` (number)
- **Успех 200**: `{"uids":[ ... ]}`
- **Ошибка 404**: `{"error":"notFound"}`

## PUT `/api/accounts/groupUsers/:gid`

Задаёт состав группы.

- **Параметр**: `gid` (number)
- **Body**: `uids` (number[])
- **Успех 204**: пустое тело
- **Ошибка 404**: `{"error":"notFound"}`

