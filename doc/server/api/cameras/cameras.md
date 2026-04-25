# `/api/cameras/cameras` — list cameras, models, servers and tree

Implemented in `server/api/cameras/cameras.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Permission is coupled to `GET /api/addresses/house/:houseId` via `#same(addresses,house,GET)` in `index()`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/cameras/cameras.php` → class `\api\cameras\cameras`.
- **Backends**:
  - `cameras` backend: `getCameras(by, query, true)`
  - `configs` backend: `getCamerasModels()`
  - `frs` backend (optional): `servers()`
  - `households` backend (optional): `getTree()`
- **Response shape**:
  - returns `{"cameras": { "cameras": [...], "models": [...], "frsServers": [...], "tree": ... }}`

## GET `/api/cameras/cameras`

### Query params

- `by` (string, optional)
- `query` (string, optional)

### Responses

- **Success 200**: `{"cameras": { ... }}` (see response shape above)

