# `/api/addresses/city` — CRUD городов

Реализация: `server/api/addresses/city.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/city.php` → `\api\addresses\city`.
- **Backend’и**:
  - backend `addresses`: `modifyCity()`, `addCity()`, `deleteCity()`.
- **Связка прав**:
  - `PUT/POST/DELETE` объявлены как `#same(addresses,house,PUT/POST/DELETE)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_cities`.
  - при удалении также удаляются записи избранного `object='city'` для этого id (через `deleteFavorite(..., all=true)`) и запускается referential `cleanup()`.
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует CRUD городов.

## PUT `/api/addresses/city/:cityId`

- **Параметр**: `cityId` (number)
- **Body**: `regionId`, `areaId`, `cityUuid`, `cityWithType`, `cityType`, `cityTypeFull`, `city`, `timezone`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/city`

- **Body**: `regionId`, `areaId`, `cityUuid`, `cityWithType`, `cityType`, `cityTypeFull`, `city`, `timezone`
- **Успех 200**: `{"cityId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/city/:cityId`

- **Параметр**: `cityId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

