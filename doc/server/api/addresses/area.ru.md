# `/api/addresses/area` — CRUD района/области

Реализация: `server/api/addresses/area.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Права на запись привязаны к `/api/addresses/house` через `#same(addresses,house,POST/PUT/DELETE)` в `index()`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/area.php` → класс `\api\addresses\area`.
- **Backend’и**: backend `addresses`:
  - `modifyArea(areaId, regionId, ...)`
  - `addArea(regionId, ...)`
  - `deleteArea(areaId)`
- **Хранилище**: в internal-variant используется таблица `addresses_areas`.

## PUT `/api/addresses/area/:areaId`

- **Параметр**: `areaId` (number)
- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/area`

- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Успех 200**: `{"areaId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/area/:areaId`

- **Параметр**: `areaId` (number)
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

# `/api/addresses/area` — CRUD районов/областей

Реализация: `server/api/addresses/area.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/area.php` → `\api\addresses\area`.
- **Backend’и**:
  - backend `addresses`: `modifyArea()`, `addArea()`, `deleteArea()`.
- **Связка прав**:
  - `PUT/POST/DELETE` объявлены как `#same(addresses,house,PUT/POST/DELETE)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_areas`.
  - при удалении также удаляются записи избранного `object='area'` для этого id (через `deleteFavorite(..., all=true)`) и запускается referential `cleanup()`.
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует CRUD районов/областей.

## PUT `/api/addresses/area/:areaId`

- **Параметр**: `areaId` (number)
- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/area`

- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Успех 200**: `{"areaId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/area/:areaId`

- **Параметр**: `areaId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

