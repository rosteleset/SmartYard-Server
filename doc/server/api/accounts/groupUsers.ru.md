# `/api/accounts/groupUsers` — состав группы

Реализация: `server/api/accounts/groupUsers.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Модель прав явно привязана к `/api/accounts/group` через `index()`:
  - `GET /api/accounts/groupUsers/:gid` использует те же права, что и `GET /api/accounts/group/:gid`
  - `PUT /api/accounts/groupUsers/:gid` использует те же права, что и `PUT /api/accounts/group/:gid`

## Зависимости

- **Точка входа / dispatch**: маршрутизируется в `server/frontend.php` через `server/api/<api>/<method>.php` и класс `\api\accounts\groupUsers`.
- **Backend’и**:
  - backend `groups`: `getUsers(gid)`, `setUsers(gid, uids)`
- **Связка прав**:
  - `index()` явно объявляет `#same(accounts,group,GET/PUT)` (то есть переиспользует модель прав от `accounts/group`).
- **Хранилище / side effects**:
  - GET 200 ответы могут кешироваться в Redis на уровне `server/frontend.php` (frontend cache).
- **Вызывается из UI**:
  - `client/modules/groups/groups.js` использует endpoint при редактировании состава группы.

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

