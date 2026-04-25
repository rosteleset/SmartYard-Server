# `/api/addresses/house` — house CRUD (+ magic create)

Implemented in `server/api/addresses/house.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/house.php` → class `\api\addresses\house`.
- **Backends**:
  - `addresses` backend: `getHouse()`, `modifyHouse()`, `addHouse()`, `addHouseByMagic()`, `deleteHouse()`.
- **Storage**:
  - backend-dependent; internal variant uses `addresses_houses` and may also create related region/area/city/settlement/street rows when using `addHouseByMagic()`.
  - internal variant uses Redis key `house_<uuid>` as input for `addHouseByMagic()` (expects JSON stored there by some upstream flow).
- **Side effects**:
  - internal variant runs referential cleanup after deletes and some writes (`cleanup()`), and periodically from `cron("5min")`.
- **Permission anchor**:
  - Many other endpoints use `#same(addresses,house,<VERB>)` — i.e. the permission set for this endpoint is reused as a baseline for region/area/city/settlement/street CRUD and for `/api/addresses/search`.
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` (create/update/delete house)
  - `client/modules/addresses/houses.js` uses `POST /api/addresses/house` with `{magic: ...}` flow.

## GET `/api/addresses/house/:houseId`

- **Params**: `houseId` (number)
- **Success 200**: `{"house": <houseObject>}`
- **Error 406**: `{"error":"notAcceptable"}`

## PUT `/api/addresses/house/:houseId`

- **Params**: `houseId` (number)
- **Body**:
  - `settlementId` (number)
  - `streetId` (number)
  - `houseUuid` (string)
  - `houseType` (string)
  - `houseTypeFull` (string)
  - `houseFull` (string)
  - `house` (string)
  - `companyId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/house`

Creates a house.

### Normal create

- **Body**: same fields as PUT (except `houseId`).
- **Success 200**: `{"houseId": <number>}`

### “Magic” create

If request body includes `magic`, the endpoint calls `addresses->addHouseByMagic(magic)`.

- **Body**:
  - `magic` (string): an id/uuid that is expected to map to Redis key `house_<magic>`
- **Success 200**: `{"houseId": <number>}`

Errors:

- If backend returns `false`: responds with default error (no explicit error code provided by the endpoint, so dispatcher will map it as `400 {"error":"unknown"}` or backend-provided last error).

## DELETE `/api/addresses/house/:houseId`

- **Params**: `houseId` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

