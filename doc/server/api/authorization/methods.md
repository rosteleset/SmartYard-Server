# `server/api/authorization/methods.php`

## Route

- **Method**: `GET`
- **Path**: `/api/authorization/methods`

## Purpose

Returns the list of API methods available on the server (as stored in `core_api_methods` after reindexing).

## Input

- `all` (boolean-ish) — passed to the backend method `methods($_all = true)`.
  - when `all=true`: returns all indexed methods
  - when `all=false`: filters out “special” methods (common, backend-driven, permissions_same)

## Implementation

Calls:

- `authorization->methods($all)`

Returns result under `"methods"` on success.

## Permissions / indexing

`index()` marks this endpoint as `#common` (see `server/utils/reindex.php`).

