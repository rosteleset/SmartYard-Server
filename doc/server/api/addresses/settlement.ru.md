# `/api/addresses/settlement` — CRUD населённых пунктов

Реализация: `server/api/addresses/settlement.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/settlement.php` → `\api\addresses\settlement`.
- **Backend’и**:
  - backend `addresses`: `modifySettlement()`, `addSettlement()`, `deleteSettlement()`.
- **Связка прав**:
  - `PUT/POST/DELETE` объявлены как `#same(addresses,house,PUT/POST/DELETE)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_settlements`.
  - при удалении также удаляются записи избранного `object='settlement'` для этого id (через `deleteFavorite(..., all=true)`) и запускается referential `cleanup()`.
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует CRUD населённых пунктов.

## PUT `/api/addresses/settlement/:settlementId`

- **Параметр**: `settlementId` (number)
- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/settlement`

- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Успех 200**: `{"settlementId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/settlement/:settlementId`

- **Параметр**: `settlementId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

