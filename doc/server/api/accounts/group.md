# `/api/accounts/group` — group CRUD

Implemented in `server/api/accounts/group.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Available HTTP methods depend on the groups backend capabilities:
  - if `groups->capabilities()["mode"] === "rw"`: `GET`, `POST`, `PUT`, `DELETE`
  - otherwise: only `GET`
  - if backend `groups` is not available: method is not exposed (empty index)

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\accounts\group`.
- **Backends**:
  - `groups` backend: `getGroup()`, `addGroup()`, `modifyGroup()`, `deleteGroup()`
- **Storage / side effects**:
  - GET 200 responses may be cached by `server/frontend.php` in Redis (frontend cache).
- **Client/UI callers**:
  - `client/modules/groups/groups.js` uses this endpoint for group CRUD and to populate group edit forms.

## GET `/api/accounts/group/:gid`

- **Params**: `gid` (number)
- **Success 200**: `{"group": <groupObject>}`
- **Error 406**: `{"error":"notAcceptable"}`

## POST `/api/accounts/group`

- **Body**:
  - `acronym` (string): short name
  - `name` (string): full group name
- **Success 200**: `{"gid": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## PUT `/api/accounts/group/:gid`

- **Params**: `gid` (number)
- **Body**:
  - `acronym` (string)
  - `name` (string)
  - `admin` (number): group admin `uid`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/accounts/group/:gid`

- **Params**: `gid` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

