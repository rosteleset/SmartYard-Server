# `/accounts/forgot` — password reset (public)

Implemented in `server/utils/forgot.php` and dispatched specially from `server/frontend.php`.

## Why it is special

`/accounts/forgot` is **not** implemented as a normal `server/api/<api>/<method>.php` endpoint:

- `server/frontend.php` has an explicit exception that **skips the Bearer-token requirement** for `accounts/forgot`.
- it is also dispatched directly by calling `forgot($params)` (no `authorization->allow()` check).

This is required because the UI calls it without any token (e.g. to check if “Forgot password” should be shown).

## GET `/accounts/forgot`

Endpoint behavior depends on query parameters.

### `?available=ask`

Used by UI as an availability probe.

- Returns **403** if:
  - `users` backend is not in `rw` mode, **or**
  - email is not configured (`!$config["email"]`)
- Otherwise returns **204**.

### `?eMail=<email>`

Issues a short-lived reset token and sends an email, if:

- the email belongs to an existing user (`users->getUidByEMail()`), and
- there is no existing `FORGOT:*:<uid>` key in Redis.

Implementation details:

- token key: `FORGOT:<token>:<uid>`
- ttl: 900 seconds
- email contains a link to `${config.api.frontend}/accounts/forgot?token=<token>`

Response is always **204** (even if user is not found / rate-limited by existing token).

### `?token=<token>`

Consumes the token and resets the password:

- finds keys `FORGOT:<token>:*`, deletes them and extracts `uid`
- generates a new password and stores it with `users->setPassword(uid, pw)`
- sends the new password to user email
- invalidates existing sessions by deleting Redis keys `AUTH:*:<uid>`

Response behavior:

- on success it writes plain text `check your mailbox for your new password` and exits
- otherwise falls through to **204**

