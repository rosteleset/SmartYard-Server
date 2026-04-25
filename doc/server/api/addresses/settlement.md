# `/api/addresses/settlement` — settlement CRUD

Implemented in `server/api/addresses/settlement.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Write permissions are coupled to `/api/addresses/house` via `#same(addresses,house,POST/PUT/DELETE)` in `index()`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/settlement.php` → class `\api\addresses\settlement`.
- **Backends**: `addresses` backend:
  - `modifySettlement(settlementId, areaId, cityId, ...)`
  - `addSettlement(areaId, cityId, ...)`
  - `deleteSettlement(settlementId)`

## PUT `/api/addresses/settlement/:settlementId`

- **Params**: `settlementId` (number)
- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Success 204**
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/settlement`

- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Success 200**: `{"settlementId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/settlement/:settlementId`

- **Params**: `settlementId` (number)
- **Success 204**
- **Error 406**: `{"error":"notAcceptable"}`

# `/api/addresses/settlement` — settlement CRUD

Implemented in `server/api/addresses/settlement.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/settlement.php` → `\api\addresses\settlement`.
- **Backends**:
  - `addresses` backend: `modifySettlement()`, `addSettlement()`, `deleteSettlement()`.
- **Permissions coupling**:
  - `PUT/POST/DELETE` are declared as `#same(addresses,house,PUT/POST/DELETE)`.
- **Storage (internal variant)**:
  - `addresses_settlements` table.
  - Deletes also remove favorites `object='settlement'` for that id (via `deleteFavorite(..., all=true)`) and run referential `cleanup()`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses settlement CRUD.

## PUT `/api/addresses/settlement/:settlementId`

- **Params**: `settlementId` (number)
- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/settlement`

- **Body**: `areaId`, `cityId`, `settlementUuid`, `settlementWithType`, `settlementType`, `settlementTypeFull`, `settlement`
- **Success 200**: `{"settlementId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/settlement/:settlementId`

- **Params**: `settlementId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

