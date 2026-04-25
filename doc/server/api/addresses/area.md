# `/api/addresses/area` — area CRUD

Implemented in `server/api/addresses/area.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/area.php` → `\api\addresses\area`.
- **Backends**:
  - `addresses` backend: `modifyArea()`, `addArea()`, `deleteArea()`.
- **Permissions coupling**:
  - `PUT/POST/DELETE` are declared as `#same(addresses,house,PUT/POST/DELETE)`.
- **Storage (internal variant)**:
  - `addresses_areas` table.
  - Deletes also remove favorites `object='area'` for that id (via `deleteFavorite(..., all=true)`) and run referential `cleanup()`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses area CRUD.

## PUT `/api/addresses/area/:areaId`

- **Params**: `areaId` (number)
- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/area`

- **Body**: `regionId`, `areaUuid`, `areaWithType`, `areaType`, `areaTypeFull`, `area`, `timezone`
- **Success 200**: `{"areaId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/area/:areaId`

- **Params**: `areaId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

