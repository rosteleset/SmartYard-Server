# `server/api/authorization/rights.php`

## Route

- **Methods**: `GET`, `POST`
- **Path**: `/api/authorization/rights`

## Purpose

Manage rights (permissions) for users and groups.

## GET

Returns all stored rights (users + groups) from the authorization backend:

- `authorization->getRights()`

Success payload key: `"rights"`.

## POST

Modifies user/group access to an API method.

The endpoint calls:

- `authorization->setRights($user, $id, $api, $method, $allow, $deny)`

Where:

- `$user` determines whether the target is a user (`true`) or a group (`false`).
- `$id` is selected from `uid` or `gid` accordingly.
- `$allow` and `$deny` are lists of method identifiers (AIDs).

Return:

- on success: `204` (because `api::ANSWER(true, false)` becomes a 204-like structure)
- on failure: `"unknown"` error

## Availability / capabilities

`index()` exposes `GET/POST` only if the active authorization backend reports:

- `capabilities()["mode"] === "rw"`

For read-only backends (e.g. allow-all), this endpoint is disabled.

