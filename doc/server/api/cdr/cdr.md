# `server/api/cdr/cdr.php`

## Route

- **Method**: `POST`
- **Path**: `/api/cdr/cdr`

## Purpose

Returns CDR (call detail record) entries for a set of phone numbers and optional time range.

This endpoint is a thin wrapper that delegates all work to the `cdr` backend via `loadBackend("cdr")->getCDR(...)`.

## Input

Body parameters (as used by the implementation):

- `phones` (`string[]`) — list of phone numbers.
- `dateFrom` (`timestamp`, optional)
- `dateTo` (`timestamp`, optional)

## Output

The endpoint returns `api::ANSWER(...)` using the backend result:

- on success: `"cdr"` payload key
- on failure: error (currently uses `"404"` string in code when backend returns `false`)

## Dependencies

- Backend: `loadBackend("cdr")`
- Backend method: `getCDR($phones, $dateFrom, $dateTo)`

## Notes

- The repository currently contains only the **base backend class** for `cdr` (`server/backends/cdr/cdr.php`). A concrete backend variant must be provided and enabled in server config (`config["backends"]["cdr"]`) for this endpoint to work.

