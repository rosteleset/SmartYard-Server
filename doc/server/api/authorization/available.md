# `server/api/authorization/available.php`

## Route

- **Method**: `GET`
- **Path**: `/api/authorization/available`

## Purpose

Returns the list of API methods available for the current user.

## Implementation

Delegates to the authorization backend:

- `authorization->allowedMethods($uid)`

and returns a success payload under the `"available"` key.

## Permissions / indexing

`index()` marks this endpoint as `#common`, which means it is included in the **common methods set** during `reindex()` (see `server/utils/reindex.php`).

