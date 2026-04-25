# `addresses` backend — base interface

Base class: `server/backends/addresses/addresses.php` (`backends\addresses\addresses`).

## Purpose

Provides CRUD-like operations for address hierarchy entities:

- regions
- areas
- cities
- settlements
- streets
- houses

The API endpoint `/api/addresses/addresses` uses this backend to return lists and single objects.

## Dependencies

- **Entry points / callers**:
  - API:
    - `server/api/addresses/addresses.php` calls list and single-object methods based on filters
  - Other backends:
    - `backends\billing\billing::importAddressHierarchy()` loads `addresses` to upsert imported hierarchy (see `server/backends/billing/billing.php`)
- **Storage**:
  - Implementation-specific:
    - internal variant uses SQL tables prefixed with `addresses_*` (see `internal.md`)
  - Backend cache:
    - inherits Redis + in-memory cache helpers from `backends\backend` (`CACHE:ADDRESSES:<key>:<uid>`)
- **Config**:
  - no required config keys are defined at the base interface level; variant determines actual requirements.

## Public interface (selected)

This base class defines a large set of abstract methods. Commonly used by the API:

- `getRegions()`
- `getAreas($regionId)`
- `getCities($regionId = false, $areaId = false)`
- `getSettlements($areaId = false, $cityId = false)`
- `getStreets($cityId = false, $settlementId = false)`
- `getHouses($settlementId = false, $streetId = false)`

Single-item accessors:

- `getArea($areaId)`, `getCity($cityId)`, `getSettlement($settlementId)`, `getStreet($streetId)`, `getHouse($houseId)`

Also includes add/modify/delete methods for each entity type.

# `addresses` backend (overview)

Base class: `server/backends/addresses/addresses.php` (`backends\addresses\addresses`).

Concrete implementations live under `server/backends/addresses/<variant>/...` (for example `server/backends/addresses/internal/internal.php`).

## Purpose

Provides address hierarchy storage and operations used by:

- Addresses API (`server/api/addresses/*`)
- Address UI (`client/modules/addresses/*`)

## Dependencies

- **Entry points / callers**:
  - API endpoints:
    - `server/api/addresses/addresses.php`
    - `server/api/addresses/search.php`
    - `server/api/addresses/house.php`
    - `server/api/addresses/region.php`
    - `server/api/addresses/area.php`
    - `server/api/addresses/city.php`
    - `server/api/addresses/settlement.php`
    - `server/api/addresses/street.php`
    - `server/api/addresses/favorites.php`
  - UI callers:
    - `client/modules/addresses/addresses.js` (full CRUD + favorites)
    - `client/modules/addresses/houses.js` (uses “magic” house create)
- **Storage (internal variant)**:
  - **DB tables**:
    - `addresses_regions`
    - `addresses_areas`
    - `addresses_cities`
    - `addresses_settlements`
    - `addresses_streets`
    - `addresses_houses`
    - `addresses_favorites`
  - **Redis keys**:
    - `house_<uuid>`: JSON blob consumed by `addHouseByMagic(uuid)`
    - backend cache keys via base backend helper: `CACHE:ADDRESSES:<key>:<uid>`
- **Config**:
  - `config["backends"]["addresses"]["text_search_mode"]` influences `searchHouse()` behavior in internal variant.
  - `config["db"]["text_search_config"]` is used for PostgreSQL full-text search mode.
- **Side effects / maintenance**:
  - internal variant runs referential `cleanup()`:
    - after deletes of address objects
    - periodically from `cron("5min")`
  - deleting an address object also clears favorites for that object across all users (uses `deleteFavorite(object, id, all=true)`).

## Notes

- `searchAddress()` in internal variant currently returns an empty array, while `searchHouse()` is implemented and supports multiple search strategies depending on DB protocol and config.
- “Magic” house creation (`addHouseByMagic`) expects an upstream component to populate Redis key `house_<uuid>` with address data.

