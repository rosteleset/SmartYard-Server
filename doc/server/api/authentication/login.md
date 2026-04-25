# `/api/authentication/login` — login

Implemented in `server/api/authentication/login.php`.

## Auth and permissions

- This endpoint is called without Bearer token.
- `server/frontend.php` treats `authentication/login` as a special case: it only enforces that `login` and `password` are provided; it does not require existing auth.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/authentication/login.php` → class `\api\authentication\login`.
- **Backends**: `authentication` backend:
  - `authentication->login(login, password, rememberMe, ua, did, ip, oneCode)`
- **Storage**:
  - `authentication->login()` stores tokens in Redis (keys `AUTH:<token>:<uid>`; may also interact with `PERSISTENT:*` depending on implementation).
- **Crypto**:
  - If Redis key `PK` exists and request has `encrypted=true`, password is decrypted via `decryptData(password, pk)`.
- **Client/UI callers**:
  - `client/js/app.js` posts to `authentication/login` (optionally sending `encrypted: true` and `oneCode` for OTP).

## POST `/api/authentication/login`

### Body

- `login` (string)
- `password` (string)
- `rememberMe` (string/bool, optional)
- `did` (string, optional): device id (used with rememberMe)
- `oneCode` (string, optional): OTP code
- `encrypted` (boolean, optional): indicates encrypted password payload

### Responses

- **Success 200**:
  - OTP required: `{"otp": true}`
  - Login success: `{"token": "<token>"}`
- **Error**: passes through backend-provided `code` and `error` (e.g. `404 {"error":"userNotFound"}`)

