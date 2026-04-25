# `/api/addresses/region` — region CRUD

Implemented in `server/api/addresses/region.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/region.php` → `\api\addresses\region`.
- **Backends**:
  - `addresses` backend: `modifyRegion()`, `addRegion()`, `deleteRegion()`.
- **Permissions coupling**:
  - `PUT/POST/DELETE` are declared as `#same(addresses,house,PUT/POST/DELETE)`.
- **Storage (internal variant)**:
  - `addresses_regions` table.
  - Deleting a region also deletes favorites with `object='region'` for that id (via backend `deleteFavorite(..., all=true)`).
  - After delete, backend runs referential `cleanup()`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses region CRUD.

## PUT `/api/addresses/region/:regionId`

- **Params**: `regionId` (number)
- **Body**: `regionUuid`, `regionIsoCode`, `regionWithType`, `regionType`, `regionTypeFull`, `region`, `timezone`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/region`

- **Body**: `regionUuid`, `regionIsoCode`, `regionWithType`, `regionType`, `regionTypeFull`, `region`, `timezone`
- **Success 200**: `{"regionId": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/region/:regionId`

- **Params**: `regionId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

