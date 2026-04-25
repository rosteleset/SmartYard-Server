# `/api/accounts/group` — CRUD группы

Реализация: `server/api/accounts/group.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Доступные HTTP-методы зависят от возможностей backend’а groups:
  - если `groups->capabilities()["mode"] === "rw"`: `GET`, `POST`, `PUT`, `DELETE`
  - иначе: только `GET`
  - если backend `groups` отсутствует: endpoint не публикуется (пустой `index()`)

## Зависимости

- **Точка входа / dispatch**: маршрутизируется в `server/frontend.php` через `server/api/<api>/<method>.php` и класс `\api\accounts\group`.
- **Backend’и**:
  - backend `groups`: `getGroup()`, `addGroup()`, `modifyGroup()`, `deleteGroup()`
- **Хранилище / side effects**:
  - GET 200 ответы могут кешироваться в Redis на уровне `server/frontend.php` (frontend cache).
- **Вызывается из UI**:
  - `client/modules/groups/groups.js` использует endpoint для CRUD групп и заполнения форм редактирования.

## GET `/api/accounts/group/:gid`

- **Параметр**: `gid` (number)
- **Успех 200**: `{"group": <groupObject>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/accounts/group`

- **Body**:
  - `acronym` (string): короткое имя
  - `name` (string): название группы
- **Успех 200**: `{"gid": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## PUT `/api/accounts/group/:gid`

- **Параметр**: `gid` (number)
- **Body**:
  - `acronym` (string)
  - `name` (string)
  - `admin` (number): `uid` администратора группы
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/accounts/group/:gid`

- **Параметр**: `gid` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

