# `/api/accounts/groupUsers` — group membership

Implemented in `server/api/accounts/groupUsers.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Permission model is explicitly tied to `/api/accounts/group` via `index()`:
  - `GET /api/accounts/groupUsers/:gid` uses the same permission as `GET /api/accounts/group/:gid`
  - `PUT /api/accounts/groupUsers/:gid` uses the same permission as `PUT /api/accounts/group/:gid`

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

