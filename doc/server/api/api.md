# `server/api/api.php` — base API class

This file defines the `api\api` base class used by API endpoint classes under `server/api/**`.

## Namespace and class

- **Namespace**: `api`
- **Base class**: `api\api`

## HTTP method handlers

The base class provides default handlers for common HTTP methods:

- `GET(array $params)`
- `POST(array $params)`
- `PUT(array $params)`
- `DELETE(array $params)`

By default, all handlers return `ANSWER(false, "badRequest")` and are expected to be overridden by specific endpoint classes.

## Return format (contract with the dispatcher)

All methods in this class return a **structured array** that encodes:

- the intended **HTTP status code** (as an array key, e.g. `"200"`, `"204"`, `400`, `403`, …)
- the **payload** (success data or an error object)
- optional **cache metadata** (as an extra array item)

### `ANSWER($result, $answer, $cache)`

`ANSWER()` is a convenience wrapper:

- if `$result === false` → returns an error array from `ERROR($answer)`
- otherwise → returns a success array from `SUCCESS($answer, $result, $cache)`

### `SUCCESS($key, $data, $cache)`

Success response encoder.

- If `$data !== false`:
  - if `$key === "__asis__"`: payload is returned “as is” under `"200"`.
  - otherwise: payload is wrapped under `"200": { "$key": $data }`.
- If `$data === false`: returns `"204" => false`.

Caching:

- `$cache` is normalized to an integer.
- if `$cache < 0`, it falls back to global `$redis_cache_ttl`.
- cache value is attached as an additional array item: `["cache" => $cache]`.

### `ERROR($error)`

Error response encoder.

- If `$error` is empty, it falls back to `getLastError()` and then to `"unknown"`.
- Known error names are mapped to HTTP status codes:
  - `badRequest` → 400
  - `forbidden` → 403
  - `notFound` → 404
  - `notAcceptable` → 406
- Unknown error names default to 400.

Returned shape:

- `{ httpCode: { "error": "<errorName>" } }`

### `index()`

`index()` is an internal helper intended for **indexing methods**. The base implementation returns `false`.

## Related code

- Entrypoint dispatchers: `server/frontend.php`, `server/internal.php`, `server/mobile.php`
- API dispatcher: `server/api/api.php`

