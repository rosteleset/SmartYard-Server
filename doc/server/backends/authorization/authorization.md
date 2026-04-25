# `server/backends/authorization/authorization.php`

## Purpose

Defines the base class for the authorization backend: `backends\authorization\authorization`.

This backend is responsible for:

- **authorization decisions** (allow/deny)
- **listing allowed methods** for a user
- **managing rights** (when supported by the active variant)
- exposing the indexed API methods list (`methods()`)

## Namespace and class

- **Namespace**: `backends\authorization`
- **Class**: `backends\authorization\authorization` (abstract)
- **Extends**: `backends\backend`

## `methods($_all = true)`

Returns a map of indexed API methods from the database tables populated by `reindex()`.

Return shape:

- `methods[api][method][request_method] = aid`

Caching:

- cached via `cacheGet/cacheSet` under key `METHODS:{0|1}`.

The `$_all=false` mode filters out:

- methods in `core_api_methods_common`
- methods in `core_api_methods_by_backend`
- methods with non-empty `permissions_same`

## Abstract contract

Variants must implement:

- `getRights()`
- `setRights($user, $id, $api, $method, $allow, $deny)`
- `allowedMethods($uid)`

## `mAllow($api, $method = false, $request_method = false)`

Convenience helper that checks `allowedMethods($this->uid)`:

- if `$request_method` is provided: checks that exact (api, method, request_method) exists
- if only `$method` is provided: checks that (api, method) exists
- if only `$api` is provided: checks that api exists

## Related code

- API endpoints: `server/api/authorization/*`
- Reindexer: `server/utils/reindex.php` populates `core_api_methods*`

