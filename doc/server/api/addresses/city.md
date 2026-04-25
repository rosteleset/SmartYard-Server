# `/api/addresses/city` — city CRUD

Implemented in `server/api/addresses/city.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/city.php` → `\api\addresses\city`.
- **Backends**:
  - `addresses` backend: `modifyCity()`, `addCity()`, `deleteCity()`.
- **Permissions coupling**:
  - `PUT/POST/DELETE` are declared as `#same(addresses,house,PUT/POST/DELETE)`.
- **Storage (internal variant)**:
  - `addresses_cities` table.
  - Deletes also remove favorites `object='city'` for that id (via `deleteFavorite(..., all=true)`) and run referential `cleanup()`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses city CRUD.

## PUT `/api/addresses/city/:cityId`

- **Params**: `cityId` (number)
- **Body**: `regionId`, `areaId`, `cityUuid`, `cityWithType`, `cityType`, `cityTypeFull`, `city`, `timezone`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/city`

- **Body**: `regionId`, `areaId`, `cityUuid`, `cityWithType`, `cityType`, `cityTypeFull`, `city`, `timezone`
- **Success 200**: `{"cityId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/city/:cityId`

- **Params**: `cityId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

