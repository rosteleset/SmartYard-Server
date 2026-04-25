# `/api/cameras/camera` — CRUD камеры

Реализация: `server/api/cameras/camera.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Права на запись привязаны к `PUT/POST/DELETE /api/addresses/house` через `#same(addresses,house,...)` в `index()`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/cameras/camera.php` → класс `\api\cameras\camera`.
- **Backend’и**: backend `cameras`:
  - `addCamera(...)`
  - `modifyCamera(cameraId, ...)`
  - `deleteCamera(cameraId)`
- **Примечание**:
  - API пробрасывает большой набор параметров напрямую в backend; валидность определяется в backend’е.

## POST `/api/cameras/camera`

- **Успех 200**: `{"cameraId": <number>}`
- **Ошибка 400**: `{"error":"unknown"}`, если backend вернул `false` (используется `ANSWER(false)` без явного кода ошибки).

## PUT `/api/cameras/camera/:cameraId`

- **Успех 204**

## DELETE `/api/cameras/camera/:cameraId`

- **Успех 204**

