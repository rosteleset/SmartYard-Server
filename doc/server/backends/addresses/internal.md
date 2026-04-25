# `addresses` backend — internal variant

Implementation: `server/backends/addresses/internal/internal.php` (`backends\addresses\internal`).

## Purpose

Stores and serves address hierarchy data from the project database.

## Dependencies

- **Storage (DB)**:
  - Uses tables (as seen in queries in this file):
    - `addresses_regions`
    - `addresses_areas`
    - (and further tables for cities/settlements/streets/houses in the same `addresses_*` family)
- **Storage (Redis)**:
  - Uses the base backend cache helpers (`CACHE:ADDRESSES:*`) where the variant chooses to cache results.
- **Callers**:
  - API endpoint `server/api/addresses/addresses.php` depends on:
    - `getRegions()`, `getAreas()`, `getCities()`, `getSettlements()`, `getStreets()`, `getHouses()`
    - and single-item methods like `getArea(id)`, `getCity(id)`, etc.
  - Billing import (`backends\billing\billing::importAddressHierarchy()`) can call add/modify methods to upsert hierarchy.

## Behavior notes

- Methods validate numeric ids using `checkInt(...)` before executing queries.
- Delete operations may trigger cleanup routines and remove related “favorites” (see calls like `deleteFavorite('region', ...)`).
- `capabilities()` returns `{"mode":"rw"}` (write operations are supported in internal mode).

