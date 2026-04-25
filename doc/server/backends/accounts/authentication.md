# `authentication` backend

Base class: `server/backends/authentication/authentication.php` (`backends\authentication\authentication`).

Concrete implementations live under `server/backends/authentication/<variant>/...`.

## Purpose

Responsible for:

- authenticating users (`login`)
- verifying tokens (`auth`)
- session/token lifecycle (`logout`)
- 2FA verification (`twoFa`)

## Dependencies

- **Entry points / callers**:
  - API endpoints (not documented here, but routed through `server/frontend.php`) call into `authentication` for login/logout and token validation.
  - `server/frontend.php` calls `authentication->auth()` for most API calls to resolve the current user.
  - Accounts API can call `authentication->logout()` via `/api/accounts/user/:uid` DELETE when `session` is provided.
- **Backends**:
  - `users` backend:
    - `users->twoFa(uid)` is used during `login()` to check if an OTP code is required
    - `users->getUidByLogin(login)` is used during `auth()` to validate that login still maps to the stored uid
- **Storage / side effects (Redis)**:
  - session tokens:
    - `AUTH:<token>:<uid>`: main token record (TTL depends on persistence and `token_idle_ttl`)
    - `PERSISTENT:<token>:<uid>`: persistent token record (used when `rememberMe` / persistent tokens are in play)
  - sudo mode:
    - `SUDO:<login>`: if present, `auth()` maps user to uid `0` (admin) temporarily and reports `sudoed` as TTL.
  - bookkeeping:
    - `LAST:LOGIN:<md5(login)>`
- **Config dependencies**:
  - `config["backends"]["authentication"]["max_allowed_tokens"]` (default 15)
  - `config["backends"]["authentication"]["token_idle_ttl"]` (default 3600)
- **Libraries**:
  - `lib/GoogleAuthenticator/GoogleAuthenticator.php` is used for OTP verification.

## Notes on behavior

- `login()` prunes old tokens when there are too many active tokens for a uid.
- Token strings are MD5-based, derived from user/login/password/device id for certain modes; otherwise random GUID-based.
- `auth()` supports:
  - `Authorization: Bearer <token>`
  - `Authorization: Base64 <b64(login)> <b64(password)>` (optionally uses header `X-Otp` for OTP)

