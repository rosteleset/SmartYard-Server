# `/api/cameras/camera` — camera CRUD

Implemented in `server/api/cameras/camera.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Write permissions are coupled to `PUT/POST/DELETE /api/addresses/house` via `#same(addresses,house,...)` in `index()`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/cameras/camera.php` → class `\api\cameras\camera`.
- **Backends**: `cameras` backend:
  - `addCamera(...)`
  - `modifyCamera(cameraId, ...)`
  - `deleteCamera(cameraId)`
- **Notes**:
  - The API forwards a large parameter set (model/url/streams/geo/FRS/areas/etc) directly to the backend; backend validation determines acceptability.

## POST `/api/cameras/camera`

- **Success 200**: `{"cameraId": <number>}`
- **Error 400**: `{"error":"unknown"}` when backend returns false (handler returns `ANSWER(false)` with no explicit error).

## PUT `/api/cameras/camera/:cameraId`

- **Success 204**

## DELETE `/api/cameras/camera/:cameraId`

- **Success 204**

