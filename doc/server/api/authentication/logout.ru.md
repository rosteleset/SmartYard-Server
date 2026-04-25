# `/api/authentication/logout` — выход

Реализация: `server/api/authentication/logout.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/authentication/logout.php` → класс `\api\authentication\logout`.
- **Backend’и**: backend `authentication`:
  - `logout(token, all=false)`
- **Хранилище / side effects (Redis)**:
  - удаляет `AUTH:<token>:<uid>` или все `AUTH:*:<uid>` в зависимости от `mode`.
- **Вызывается из UI**:
  - `client/js/app.js` вызывает `POST("authentication","logout", ...)`.

## POST `/api/authentication/logout`

### Body

- `mode` (string, опционально): `"all"` или `"this"` (в коде всё кроме `"all"` означает “только этот токен”)

### Ответы

- **Успех 204**: пустое тело

