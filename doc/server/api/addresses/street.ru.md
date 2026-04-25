# `/api/addresses/street` — CRUD улицы

Реализация: `server/api/addresses/street.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Права на запись привязаны к `/api/addresses/house` через `#same(addresses,house,POST/PUT/DELETE)` в `index()`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/street.php` → класс `\api\addresses\street`.
- **Backend’и**: backend `addresses`:
  - `modifyStreet(streetId, cityId, settlementId, ...)`
  - `addStreet(cityId, settlementId, ...)`
  - `deleteStreet(streetId)`

## PUT `/api/addresses/street/:streetId`

- **Параметр**: `streetId` (number)
- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/street`

- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Успех 200**: `{"streetId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/street/:streetId`

- **Параметр**: `streetId` (number)
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

# `/api/addresses/street` — CRUD улиц

Реализация: `server/api/addresses/street.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/street.php` → `\api\addresses\street`.
- **Backend’и**:
  - backend `addresses`: `modifyStreet()`, `addStreet()`, `deleteStreet()`.
- **Связка прав**:
  - `PUT/POST/DELETE` объявлены как `#same(addresses,house,PUT/POST/DELETE)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_streets`.
  - при удалении также удаляются записи избранного `object='street'` для этого id (через `deleteFavorite(..., all=true)`) и запускается referential `cleanup()`.
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует CRUD улиц.

## PUT `/api/addresses/street/:streetId`

- **Параметр**: `streetId` (number)
- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/street`

- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Успех 200**: `{"streetId": <number>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/street/:streetId`

- **Параметр**: `streetId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

