# `/api/accounts/groupUsers` — group membership

Implemented in `server/api/accounts/groupUsers.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Permission model is explicitly tied to `/api/accounts/group` via `index()`:
  - `GET /api/accounts/groupUsers/:gid` uses the same permission as `GET /api/accounts/group/:gid`
  - `PUT /api/accounts/groupUsers/:gid` uses the same permission as `PUT /api/accounts/group/:gid`

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\accounts\groupUsers`.
- **Backends**:
  - `groups` backend: `getUsers(gid)`, `setUsers(gid, uids)`
- **Permissions coupling**:
  - `index()` explicitly declares `#same(accounts,group,GET/PUT)` (i.e. it reuses the permission model from `accounts/group`).
- **Storage / side effects**:
  - GET 200 responses may be cached by `server/frontend.php` in Redis (frontend cache).
- **Client/UI callers**:
  - `client/modules/groups/groups.js` uses it when editing group members.

## GET `/api/accounts/groupUsers/:gid`

Returns list of user ids in the group.

- **Params**: `gid` (number)
- **Success 200**: `{"uids":[ ... ]}`
- **Error 404**: `{"error":"notFound"}`

## PUT `/api/accounts/groupUsers/:gid`

Sets group membership.

- **Params**: `gid` (number)
- **Body**: `uids` (number[])
- **Success 204**: empty body
- **Error 404**: `{"error":"notFound"}`

