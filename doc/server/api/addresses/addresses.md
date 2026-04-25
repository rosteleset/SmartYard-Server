# `/api/addresses/addresses` — address hierarchy lookup

Implemented in `server/api/addresses/addresses.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Endpoint is available only if backend `addresses` exists (`loadBackend("addresses")`).

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\addresses\addresses`.
- **Backends**:
  - `addresses` backend:
    - list methods: `getRegions()`, `getAreas(regionId)`, `getCities(regionId, areaId)`, `getSettlements(areaId, cityId)`, `getStreets(cityId, settlementId)`, `getHouses(settlementId, streetId)`
    - single-item methods (when `*Id` filter is provided): `getArea(areaId)`, `getCity(cityId)`, `getSettlement(settlementId)`, `getStreet(streetId)`, `getHouse(houseId)`
- **Storage / side effects**:
  - GET 200 responses may be cached by `server/frontend.php` in Redis (frontend cache).
- **Config**:
  - no direct config keys are required by this endpoint (beyond the backend configuration).

## GET `/api/addresses/addresses`

Returns a JSON object containing one or more address hierarchy collections. Which collections are returned is controlled by the `include` parameter.

### Query params

- `regionId` (number, optional)
- `areaId` (number, optional)
- `cityId` (number, optional)
- `settlementId` (number, optional)
- `streetId` (number, optional)
- `houseId` (number, optional)
- `include` (string, optional): comma-separated set of collections to return.
  - default: `regions,areas,cities,settlements,streets,houses`
  - recognized values (as matched by `strpos()`): `regions`, `areas`, `cities`, `settlements`, `streets`, `houses`

### Behavior notes

- Each collection is fetched independently if its name is present in `include`.
- For `areas/cities/settlements/streets/houses`:
  - if the corresponding `*Id` parameter is provided (non-zero), the API returns a single-element array: `[ getX(id) ]`
  - otherwise it returns a list filtered by parent ids (e.g. `getAreas(regionId)`, `getStreets(cityId, settlementId)`)

### Responses

- **Success 200**: `{"addresses": { "regions": [...], "areas": [...], ... }}`
- **Error 400**: `{"error":"badRequest"}`
  - returned if the backend call chain fails and the handler returns `ANSWER(false, "badRequest")`

# `/api/addresses/addresses` — hierarchical address lists

Implemented in `server/api/addresses/addresses.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/addresses.php` → class `\api\addresses\addresses::GET()`.
- **Backends**:
  - `addresses` backend (`loadBackend("addresses")`): `getRegions()`, `getAreas()`, `getArea()`, `getCities()`, `getCity()`, `getSettlements()`, `getSettlement()`, `getStreets()`, `getStreet()`, `getHouses()`, `getHouse()`.
- **Storage**:
  - backend-dependent; internal variant reads `addresses_*` tables (regions/areas/cities/settlements/streets/houses).
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` uses it as the primary source of address hierarchy data.

## GET `/api/addresses/addresses`

Returns a structured object that may include: regions, areas, cities, settlements, streets, houses.

### Query params

- `regionId` (number, optional)
- `areaId` (number, optional)
- `cityId` (number, optional)
- `settlementId` (number, optional)
- `streetId` (number, optional)
- `houseId` (number, optional)
- `include` (string, optional, default `"regions,areas,cities,settlements,streets,houses"`):
  a comma-separated list that controls which collections are included.

### Success response

- **200**: `{"addresses": { "regions": [...], "areas": [...], ... }}`

Notes:

- If an `*Id` is provided for a collection, the API returns a single-item array for that collection (e.g. `areas: [getArea(areaId)]`).

