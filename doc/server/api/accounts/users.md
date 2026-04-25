# `/api/accounts/users` — users list

Implemented in `server/api/accounts/users.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\accounts\users`.
- **Backends**:
  - `users` backend: `getUsers(withSessions, withLast)`
- **Storage / side effects**:
  - GET 200 responses may be cached by `server/frontend.php` in Redis (frontend cache).
- **Client/UI callers**:
  - `client/modules/users/users.js` (users list, sometimes with `withSessions`)
  - `client/modules/tt/settings.js` (reads users list)

## GET `/api/accounts/users`

Returns a list of users.

### Query params

- `withSessions` (boolean, optional): include sessions data (backend-specific)
- `withLast` (boolean, optional): include “last activity” data (backend-specific)

### Responses

- **Success 200**: `{"users":[ ... ]}`
- **Error 404**: `{"error":"notFound"}`

