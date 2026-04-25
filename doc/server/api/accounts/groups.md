# `/api/accounts/groups` — groups list

Implemented in `server/api/accounts/groups.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Endpoint is available only if backend `groups` exists (`loadBackend("groups")`).

## GET `/api/accounts/groups`

Returns list of groups.

### Responses

- **Success 200**: `{"groups":[ ... ]}`
- **Error 404**: `{"error":"notFound"}`

