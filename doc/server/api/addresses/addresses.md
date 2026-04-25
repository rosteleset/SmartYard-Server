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

