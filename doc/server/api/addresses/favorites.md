# `/api/addresses/favorites` — favorites CRUD

Implemented in `server/api/addresses/favorites.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/addresses/favorites.php` → `\api\addresses\favorites`.
- **Backends**:
  - `addresses` backend: `getFavorites()`, `addFavorite(object,id,title,icon,color)`, `deleteFavorite(object,id)`.
- **Storage (internal variant)**:
  - `addresses_favorites` table.
  - Important behavior difference in backend:
    - user-driven delete removes row(s) for the current `login`
    - cleanup delete (when deleting an address object) removes favorites for all users (`all=true`)
- **Client/UI callers**:
  - `client/modules/addresses/addresses.js` (bookmark/favorite sidebar entries and toggle favorite UI).

## GET `/api/addresses/favorites`

- **Success 200**: `{"favorites":[ ... ]}`

## POST `/api/addresses/favorites`

- **Body**:
  - `object` (string): one of `area`, `region`, `city`, `settlement`, `street`, `house`
  - `id` (number)
  - `title` (string)
  - `icon` (string)
  - `color` (string)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/favorites`

- **Body**:
  - `object` (string)
  - `id` (number)
- **Success 204**: empty body
- **Error 406**: `{"error":"notAcceptable"}`

