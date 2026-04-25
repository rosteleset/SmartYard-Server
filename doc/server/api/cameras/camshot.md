# `/api/cameras/camshot` — snapshot

Implemented in `server/api/cameras/camshot.php` (and optionally overridden by `server/api/cameras/custom/camshot.php`).

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Permission is coupled to `GET /api/addresses/house/:houseId` via `#same(addresses,house,GET)` in `index()`.

## Dependencies

- **Entry point / dispatch**:
  - default: `server/frontend.php` → `server/api/cameras/camshot.php` → class `\api\cameras\camshot`
  - custom override (if present): `server/api/cameras/custom/camshot.php` → class `\api\cameras\custom\camshot` which extends base implementation
- **Backends**: `cameras` backend:
  - `getCameras("id", cameraId)`
  - `getSnapshot(cameraId)`
- **Data transformation**:
  - snapshot binary is base64-encoded before returning.
- **Response shape**:
  - on success returns `{"shot":"<base64>"}`.

## GET `/api/cameras/camshot/:cameraId`

- **Params**: `cameraId` (number)
- **Success 200**: `{"shot":"<base64>"}`
- **Error 400**: `{"error":"unknown"}` when no snapshot is available (handler returns `ANSWER(false)`).

