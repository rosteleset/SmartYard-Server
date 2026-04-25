# `server/backends/cdr/cdr.php`

## Purpose

Defines the base backend class for CDR (call detail records).

This is the “module-level” backend class that concrete variants should extend.

## Namespace and class

- **Namespace**: `backends\cdr`
- **Class**: `backends\cdr\cdr` (abstract)
- **Extends**: `backends\backend`

## Contract

Concrete CDR backend variants must implement:

- `getCDR($phones, $dateFrom, $dateTo)`

### Parameters

- `$phones`: list of phone numbers (as provided by API).
- `$dateFrom`: optional timestamp lower bound.
- `$dateTo`: optional timestamp upper bound.

### Return value

The return value is consumed by `server/api/cdr/cdr.php`:

- return **data** (any structured array) on success
- return `false` on failure (API will treat it as an error)

## Related code

- API endpoint: `server/api/cdr/cdr.php`
- Loader: `server/utils/loader.php` (`loadBackend("cdr")`)
- Configuration: `server/config/config.json` → `backends.cdr`

## Notes

- This repository currently contains only the base abstract class for `cdr`. A concrete variant is expected to live under:
  - `server/backends/cdr/<variant>/<variant>.php`
  and be selected via `config["backends"]["cdr"]["backend"] = "<variant>"`.

