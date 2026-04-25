# Variant: `internal` (`server/backends/authorization/internal/internal.php`)

## Purpose

Database-driven authorization backend.

This variant implements:

- allow/deny decisions based on indexed API methods and stored rights
- reading/writing rights for users and groups
- listing allowed methods for a user

## Key concepts

Authorization is tied to the indexed method identifier **AID** from `core_api_methods`, populated by `reindex()`.

Special buckets:

- **common** methods (`core_api_methods_common`)
- **personal** methods (`core_api_methods_personal`) — allowed when `params["_id"] == uid`
- **by-backend** methods (`core_api_methods_by_backend`) — delegated to another backend’s `allow()`
- **permissions_same** — method permissions aliasing (`#same(...)` in API `index()`)

## `allow($params)`

Main decision function.

High-level behavior:

- Always allows `authentication/login`.
- Rejects if `params["_uid"]` is not an integer.
- Resolves “permissions_same” aliasing: if the requested method points to another method for permissions, checks rights against the target triple.
- If the method is marked `by_backend`, delegates to that backend:
  - `loadBackend(<backend>)->allow($_params)`
- Admin user (`uid === 0`) is always allowed.
- Otherwise checks if AID is allowed by:
  - group allow rules (+ common methods)
  - user allow rules
  - minus group/user deny rules
- For personal methods, additionally allows when `params["_id"] == uid` and the method is in `core_api_methods_personal`.

## `allowedMethods($uid)`

Returns a map of methods allowed for a user:

- `{ api: { method: { request_method: aid } } }`

Logic:

- admin (`uid === 0`) → returns `methods()` (all indexed methods)
- otherwise builds the list from DB:
  - group allow + common + personal + by-backend
  - minus group/user deny
  - plus explicit user allow
  - then re-adds entries that have `permissions_same` pointing to allowed AIDs

Caching:

- cached under key `ALLOWED:<uid>` via backend cache helpers.

## Rights management

- `getRights()` returns `users` and `groups` rights rows.
- `setRights(...)` clears backend cache and rewrites allow/deny rows for the given `(uid|gid, api, method)` pair.
- `capabilities()` returns `["mode" => "rw"]` which enables `api/authorization/rights`.

## Related code

- Reindexer: `server/utils/reindex.php`
- API endpoints: `server/api/authorization/*`

