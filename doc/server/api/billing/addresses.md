# `/api/billing/addresses` — import address hierarchy

Implemented in `server/api/billing/addresses.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/billing/addresses.php` → class `\api\billing\addresses`.
- **Backends**:
  - `billing` backend: `importAddressHierarchy(items)`
  - `importAddressHierarchy()` further loads and depends on:
    - `addresses` backend
    - `households` backend
    - `customFields` backend
- **Storage / side effects**:
  - Import upserts address hierarchy and flats into DB via the above backends.

## POST `/api/billing/addresses`

### Body

- `addresses` (object[]): address hierarchy items (see `server/api/billing/addresses.php` docblock for the full field list).

### Responses

- **Success 200**: `{"addresses": <importResult>}`
- **Error 400**: `{"error":"badRequest"}` when `addresses` is missing or not an array.
- **Error 400**: `{"error":"unknown"}` when billing backend is missing (handler returns `ANSWER(false)` with no explicit error name).

