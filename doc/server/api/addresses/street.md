# `/api/addresses/street` — street CRUD

Implemented in `server/api/addresses/street.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Write permissions are coupled to `/api/addresses/house` via `#same(addresses,house,POST/PUT/DELETE)` in `index()`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/street.php` → class `\api\addresses\street`.
- **Backends**: `addresses` backend:
  - `modifyStreet(streetId, cityId, settlementId, ...)`
  - `addStreet(cityId, settlementId, ...)`
  - `deleteStreet(streetId)`

## PUT `/api/addresses/street/:streetId`

- **Params**: `streetId` (number)
- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Success 204**
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/street`

- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Success 200**: `{"streetId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/street/:streetId`

- **Params**: `streetId` (number)
- **Success 204**
- **Error 406**: `{"error":"notAcceptable"}`

# `/api/addresses/street` — street CRUD

Implemented in `server/api/addresses/street.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/street.php` → `\api\addresses\street`.
- **Backends**:
  - `addresses` backend: `modifyStreet()`, `addStreet()`, `deleteStreet()`.
- **Permissions coupling**:
  - `PUT/POST/DELETE` are declared as `#same(addresses,house,PUT/POST/DELETE)`.
- **Storage (internal variant)**:
  - `addresses_streets` table.
  - Deletes also remove favorites `object='street'` for that id (via `deleteFavorite(..., all=true)`) and run referential `cleanup()`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses street CRUD.

## PUT `/api/addresses/street/:streetId`

- **Params**: `streetId` (number)
- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/street`

- **Body**: `cityId`, `settlementId`, `streetUuid`, `streetWithType`, `streetType`, `streetTypeFull`, `street`
- **Success 200**: `{"streetId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/street/:streetId`

- **Params**: `streetId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

