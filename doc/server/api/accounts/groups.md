# `/api/accounts/groups` — groups list

Implemented in `server/api/accounts/groups.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Endpoint is available only if backend `groups` exists (`loadBackend("groups")`).

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\accounts\groups`.
- **Backends**:
  - `groups` backend: `getGroups(false)`
- **Storage / side effects**:
  - GET 200 responses may be cached by `server/frontend.php` in Redis (frontend cache).
- **Client/UI callers**:
  - `client/modules/groups/groups.js` uses it to display the groups list.

## GET `/api/accounts/groups`

Returns list of groups.

### Responses

- **Success 200**: `{"groups":[ ... ]}`
- **Error 404**: `{"error":"notFound"}`

