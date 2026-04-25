# `/api/accounts/user` — user CRUD

Implemented in `server/api/accounts/user.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.
- Available HTTP methods depend on the users backend capabilities:
  - if `users->capabilities()["mode"] === "rw"`: `GET`, `POST`, `PUT`, `DELETE`
  - otherwise: only `GET`

## Dependencies

- **Entry point / dispatch**: routed by `server/frontend.php` via `server/api/<api>/<method>.php` and class `\api\accounts\user`.
- **Backends**:
  - `users` backend: `getUser()`, `addUser()`, `modifyUser()`, `putAvatar()`, `setPassword()`, `deleteUser()`
  - `authentication` backend (DELETE only, if `session` provided): `logout(session, false)`
  - `groups` backend (PUT only, if `userGroups` provided): `setGroups(uid, gids)` (loaded via `loadBackend("groups")`)
- **Storage / side effects**:
  - Standard GET responses may be cached by `server/frontend.php` in Redis (frontend cache); this endpoint itself does not manage keys directly.
- **Client/UI callers**:
  - `client/modules/users/users.js` uses this endpoint for user CRUD in the Users UI.

## GET `/api/accounts/user/:uid`

Returns a single user.

- **Params**: `uid` (number)
- **Success 200**: `{"user": <userObject>}`
- **Error 404**: `{"error":"notFound"}`

## POST `/api/accounts/user`

Creates a user.

- **Body**:
  - `login` (string)
  - `realName` (string)
  - `eMail` (string)
  - `phone` (string)
- **Success 200**: `{"uid": <number>}`
- **Error 406**: `{"error":"notAcceptable"}`

## PUT `/api/accounts/user/:uid`

Updates a user.

- **Params**: `uid` (number)
- **Body** (as used by `modifyUser()` and optional operations):
  - `realName`, `eMail`, `phone`
  - `tg` (Telegram id), `notification`
  - `enabled` (boolean)
  - `defaultRoute` (string), `persistentToken` (string)
  - `primaryGroup` (number, optional)
  - `serviceAccount` (boolean, optional)
  - `sudo` (boolean, optional)
  - `avatar` (object/data-url, optional): if present, also calls `users->putAvatar()`
  - `userGroups` (number[], optional): if present, also calls `groups->setGroups(uid, gids)`
  - `password` (string, optional): if present and `uid` is non-zero, also calls `users->setPassword()`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/accounts/user/:uid`

Deletes a user, or logs out a session.

- **Params**: `uid` (number)
- **Optional body/query**: `session` (string)
  - if `session` is provided, performs `authentication->logout(session, false)`
  - otherwise deletes user via `users->deleteUser(uid)`
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

