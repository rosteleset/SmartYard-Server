# `/api/authentication/logout` — logout

Implemented in `server/api/authentication/logout.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/authentication/logout.php` → class `\api\authentication\logout`.
- **Backends**: `authentication` backend:
  - `logout(token, all=false)`
- **Storage / side effects (Redis)**:
  - deletes `AUTH:<token>:<uid>` or all `AUTH:*:<uid>` depending on `mode`.
- **Client/UI callers**:
  - `client/js/app.js` calls `POST("authentication","logout", ...)`.

## POST `/api/authentication/logout`

### Body

- `mode` (string, optional): `"all"` or `"this"` (code treats anything other than `"all"` as “single token”)

### Responses

- **Success 204**: empty body

