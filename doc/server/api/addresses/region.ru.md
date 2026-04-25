# `/api/addresses/region` — CRUD регионов

Реализация: `server/api/addresses/region.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/region.php` → `\api\addresses\region`.
- **Backend’и**:
  - backend `addresses`: `modifyRegion()`, `addRegion()`, `deleteRegion()`.
- **Связка прав**:
  - `PUT/POST/DELETE` объявлены как `#same(addresses,house,PUT/POST/DELETE)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_regions`.
  - при удалении региона backend также удаляет записи избранного `object='region'` для этого id (через `deleteFavorite(..., all=true)`).
  - после удаления запускается referential `cleanup()`.
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует CRUD регионов.

## PUT `/api/addresses/region/:regionId`

- **Параметр**: `regionId` (number)
- **Body**: `regionUuid`, `regionIsoCode`, `regionWithType`, `regionType`, `regionTypeFull`, `region`, `timezone`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/region`

- **Body**: `regionUuid`, `regionIsoCode`, `regionWithType`, `regionType`, `regionTypeFull`, `region`, `timezone`
- **Успех 200**: `{"regionId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/region/:regionId`

- **Параметр**: `regionId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

