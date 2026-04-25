# `/api/accounts/user` — CRUD пользователя

Реализация: `server/api/accounts/user.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Доступные HTTP-методы зависят от возможностей backend’а users:
  - если `users->capabilities()["mode"] === "rw"`: `GET`, `POST`, `PUT`, `DELETE`
  - иначе: только `GET`

## GET `/api/accounts/user/:uid`

Возвращает одного пользователя.

- **Параметр**: `uid` (number)
- **Успех 200**: `{"user": <userObject>}`
- **Ошибка 404**: `{"error":"notFound"}`

## POST `/api/accounts/user`

Создаёт пользователя.

- **Body**:
  - `login` (string)
  - `realName` (string)
  - `eMail` (string)
  - `phone` (string)
- **Успех 200**: `{"uid": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## PUT `/api/accounts/user/:uid`

Обновляет пользователя.

- **Параметр**: `uid` (number)
- **Body** (как используется в `modifyUser()` и опциональных ветках):
  - `realName`, `eMail`, `phone`
  - `tg` (Telegram id), `notification`
  - `enabled` (boolean)
  - `defaultRoute` (string), `persistentToken` (string)
  - `primaryGroup` (number, опционально)
  - `serviceAccount` (boolean, опционально)
  - `sudo` (boolean, опционально)
  - `avatar` (object/data-url, опционально): дополнительно вызывает `users->putAvatar()`
  - `userGroups` (number[], опционально): дополнительно вызывает `groups->setGroups(uid, gids)`
  - `password` (string, опционально): если передан и `uid` ненулевой, дополнительно вызывает `users->setPassword()`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/accounts/user/:uid`

Удаляет пользователя или завершает сессию.

- **Параметр**: `uid` (number)
- **Опционально (body/query)**: `session` (string)
  - если `session` передан, выполняется `authentication->logout(session, false)`
  - иначе удаление через `users->deleteUser(uid)`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

