# `/api/cameras/camshot` — снимок

Реализация: `server/api/cameras/camshot.php` (и опционально override в `server/api/cameras/custom/camshot.php`).

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Право привязано к `GET /api/addresses/house/:houseId` через `#same(addresses,house,GET)` в `index()`.

## Зависимости

- **Точка входа / dispatch**:
  - по умолчанию: `server/frontend.php` → `server/api/cameras/camshot.php` → класс `\api\cameras\camshot`
  - custom override (если есть): `server/api/cameras/custom/camshot.php` → класс `\api\cameras\custom\camshot`, который наследуется от базового
- **Backend’и**: backend `cameras`:
  - `getCameras("id", cameraId)`
  - `getSnapshot(cameraId)`
- **Преобразование данных**:
  - бинарный снимок кодируется в base64.
- **Форма ответа**:
  - при успехе `{"shot":"<base64>"}`.

## GET `/api/cameras/camshot/:cameraId`

- **Параметр**: `cameraId` (number)
- **Успех 200**: `{"shot":"<base64>"}`
- **Ошибка 400**: `{"error":"unknown"}`, если снимок получить не удалось (используется `ANSWER(false)`).

