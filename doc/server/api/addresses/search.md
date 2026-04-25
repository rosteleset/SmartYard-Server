# `/api/addresses/search` — full-text address search

Implemented in `server/api/addresses/search.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Permission is coupled to `GET /api/addresses/house/:houseId` via `#same(addresses,house,GET)` in `index()`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/search.php` → class `\api\addresses\search`.
- **Backends**: `addresses` backend:
  - `searchAddress(searchString)`
- **Storage**: backend-defined (internal variant queries `addresses_*` tables).

## GET `/api/addresses/search`

- **Query**: `search` (string)
- **Success 200**: `{"addresses":[ ... ]}`
- **Error 400**: `{"error":"unknown"}` (handler returns `ANSWER(false)` without explicit error name)

# `/api/addresses/search` — address search

Implemented in `server/api/addresses/search.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/search.php` → `\api\addresses\search::GET()`.
- **Backends**:
  - `addresses` backend: `searchAddress(search)`.
    - Note: internal addresses variant currently implements `searchAddress()` as `return [];` (empty results).
- **Permissions coupling**:
  - `index()` declares `GET => #same(addresses,house,GET)`, so access is controlled like `GET /api/addresses/house/:houseId`.
- **Client/UI callers**:
  - address UI uses search features (module `_search` under `client/modules/addresses/`).

## GET `/api/addresses/search`

- **Query**: `search` (string)
- **Success 200**: `{"addresses":[ ... ]}`
- **Success 204**: empty body (if backend returns `false`, the base API encodes `204`; behavior depends on backend implementation)

