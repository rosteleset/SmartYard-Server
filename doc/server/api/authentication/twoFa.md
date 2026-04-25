# `/api/authentication/twoFa` — 2FA request/confirm

Implemented in `server/api/authentication/twoFa.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/authentication/twoFa.php` → class `\api\authentication\twoFa`.
- **Backends**: `authentication` backend:
  - `twoFa(token, oneCode)`
- **Libraries**:
  - underlying backend uses GoogleAuthenticator for OTP verification (see `server/backends/authentication/authentication.php`).

## POST `/api/authentication/twoFa`

### Body

- `oneCode` (string, optional): OTP code (used to confirm)

### Responses

- **Success 200**: `{"twoFa": <boolean|object>}` (backend-defined shape)
- **Error 406**: `{"error":"notAcceptable"}`

