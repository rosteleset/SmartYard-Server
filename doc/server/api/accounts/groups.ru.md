# `/api/accounts/groups` — список групп

Реализация: `server/api/accounts/groups.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Endpoint доступен только если существует backend `groups` (`loadBackend("groups")`).

## Зависимости

- **Точка входа / dispatch**: маршрутизируется в `server/frontend.php` через `server/api/<api>/<method>.php` и класс `\api\accounts\groups`.
- **Backend’и**:
  - backend `groups`: `getGroups(false)`
- **Хранилище / side effects**:
  - GET 200 ответы могут кешироваться в Redis на уровне `server/frontend.php` (frontend cache).
- **Вызывается из UI**:
  - `client/modules/groups/groups.js` использует endpoint для отображения списка групп.

## GET `/api/accounts/groups`

Возвращает список групп.

### Ответы

- **Успех 200**: `{"groups":[ ... ]}`
- **Ошибка 404**: `{"error":"notFound"}`

