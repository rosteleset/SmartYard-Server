# `server/utils/reindex.php`

`reindex()` scans `server/api/**` and populates database tables used by the authorization subsystem.

## Purpose

Build and refresh the indexed list of API methods, their HTTP verbs, and special permission handling flags.

The output of `reindex()` is consumed primarily by:

- `backends\authorization\authorization::methods()`
- `backends\authorization\internal` (for allow/deny and allowedMethods)

## What it indexes

For each API namespace folder `server/api/<api>/` it scans method files from:

- `server/api/<api>/*.php`
- `server/api/<api>/custom/*.php` (custom overrides take precedence)

It then loads the file and calls the API class static `index()` method to get the supported request methods.

## How `index()` is interpreted

`index()` may return a list or a map.

- If it returns a list like `["GET", "POST"]`, each element is treated as an HTTP method.
- If it returns a map like `["GET" => "#common"]`, the key is the HTTP method and the value is a special tag.

Special tags understood by `reindex()`:

- `#common` → store AID in `core_api_methods_common`
- `#personal` → store AID in `core_api_methods_personal`
- `#same(api, method, request_method)` → store alias in `core_api_methods.permissions_same`
- any other string → treated as a backend name and stored in `core_api_methods_by_backend`

## AID generation

Each (api, method, request_method) triple is assigned:

- `aid = md5("$api/$method/$request_method")`

## Database tables affected

`reindex()` truncates and rebuilds:

- `core_api_methods`
- `core_api_methods_common`
- `core_api_methods_by_backend`
- `core_api_methods_personal`

Finally it removes invalid `permissions_same` references.

