# `server/api/authorization/rights.php`

## Маршрут

- **Методы**: `GET`, `POST`
- **Путь**: `/api/authorization/rights`

## Назначение

Управление правами (permissions) пользователей и групп.

## GET

Возвращает все сохранённые права (users + groups) из backend’а authorization:

- `authorization->getRights()`

Ключ успешного payload: `"rights"`.

## POST

Изменяет доступ пользователя/группы к API-методу.

Endpoint вызывает:

- `authorization->setRights($user, $id, $api, $method, $allow, $deny)`

Где:

- `$user` определяет, кто целевой объект: пользователь (`true`) или группа (`false`).
- `$id` выбирается из `uid` или `gid` соответственно.
- `$allow` и `$deny` — списки идентификаторов методов (AID).

Возврат:

- при успехе: `204` (т.к. `api::ANSWER(true, false)` даёт 204-подобную структуру)
- при ошибке: ошибка `"unknown"`

## Доступность / capabilities

`index()` возвращает `GET/POST` только если активный backend authorization сообщает:

- `capabilities()["mode"] === "rw"`

Для read-only backend’ов (например allow-all) этот endpoint отключён.

