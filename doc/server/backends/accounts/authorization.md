# `authorization` backend

Base class: `server/backends/authorization/authorization.php` (`backends\authorization\authorization`).

Concrete implementations live under `server/backends/authorization/<variant>/...` (for example `server/backends/authorization/internal/internal.php`).

## Purpose

Controls access to API methods and provides:

- the full list of API methods on the server (`methods()`)
- rights management (`getRights()`, `setRights()`)
- per-user method availability (`allowedMethods(uid)`)

`server/frontend.php` enforces permissions by calling `authorization->allow($params)` before dispatching an API endpoint.

## Dependencies

- **Entry points / callers**:
  - `server/frontend.php` calls `authorization->allow($params)` to decide whether a request is permitted.
  - API endpoints under `server/api/authorization/*` call:
    - `allowedMethods(uid)` (`/api/authorization/available`)
    - `methods(all)` (`/api/authorization/methods`)
    - `getRights()` / `setRights(...)` (`/api/authorization/rights`)
  - UI uses `/api/authorization/available` to drive `AVAIL(...)` checks for features/menus.
- **Storage (DB)**:
  - base implementation of `methods($_all)` queries `core_api_methods` (and related helper tables such as `core_api_methods_common`, `core_api_methods_by_backend`).
  - internal variant uses rights tables (e.g. `core_users_rights`, `core_groups_rights`) to implement permission checks and rights editing.
- **Storage (Redis)**:
  - uses base backend cache helpers for `methods()` results:
    - key is `CACHE:AUTHORIZATION:METHODS:<1|0>:<uid>` (via `cacheGet/cacheSet`)
- **Config**:
  - relies on global DB schema that defines method catalog and rights tables.
- **Variants**:
  - `server/backends/authorization/allow/allow.php` is an allow-all variant:
    - `allow()` always returns true
    - `allowedMethods(uid)` returns `methods()`
    - `capabilities()` returns false (no rights editing)

## Public interface

- `methods($_all = true)` (implemented in base class)
- `getRights()` (abstract)
- `setRights($user, $id, $api, $method, $allow, $deny)` (abstract)
- `allowedMethods($uid)` (abstract)
- `mAllow($api, $method = false, $request_method = false)` helper for checking availability for current backend credentials

