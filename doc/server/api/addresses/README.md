# Addresses (`addresses/*`)

This section documents the addresses-related API endpoints implemented under `server/api/addresses/*`.

## Auth and permissions

- All endpoints require `Authorization: Bearer <token>`.
- Requests are routed by `server/frontend.php` and allowed/denied via `authorization->allow($params)`.
- Many write endpoints (and `search`) explicitly reuse the permission model of `/api/addresses/house` via `#same(addresses,house,...)`.

## Index

- [`/api/addresses/addresses` — hierarchical address lists](./addresses.md)
- [`/api/addresses/search` — address search](./search.md)
- [`/api/addresses/house` — house CRUD (+ magic create)](./house.md)
- [`/api/addresses/region` — region CRUD](./region.md)
- [`/api/addresses/area` — area CRUD](./area.md)
- [`/api/addresses/city` — city CRUD](./city.md)
- [`/api/addresses/settlement` — settlement CRUD](./settlement.md)
- [`/api/addresses/street` — street CRUD](./street.md)
- [`/api/addresses/favorites` — favorites CRUD](./favorites.md)

