# `/api/accounts/users` — users list

Implemented in `server/api/accounts/users.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## GET `/api/accounts/users`

Returns a list of users.

### Query params

- `withSessions` (boolean, optional): include sessions data (backend-specific)
- `withLast` (boolean, optional): include “last activity” data (backend-specific)

### Responses

- **Success 200**: `{"users":[ ... ]}`
- **Error 404**: `{"error":"notFound"}`

