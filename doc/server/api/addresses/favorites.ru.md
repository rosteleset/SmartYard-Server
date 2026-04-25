# `/api/addresses/favorites` — избранное

Реализация: `server/api/addresses/favorites.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/favorites.php` → класс `\api\addresses\favorites`.
- **Backend’и**: backend `addresses`:
  - `getFavorites()`
  - `addFavorite(object, id, title, icon, color)`
  - `deleteFavorite(object, id)`
- **Хранилище**:
  - internal-variant хранит избранное в БД (точные таблицы см. в `server/backends/addresses/internal/internal.php`).

## GET `/api/addresses/favorites`

- **Успех 200**: `{"favorites":[ ... ]}`
- **Ошибка 400**: `{"error":"badRequest"}`

## POST `/api/addresses/favorites`

- **Body**:
  - `object` (string): `area|region|city|settlement|street|house`
  - `id` (number)
  - `title` (string)
  - `icon` (string)
  - `color` (string)
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/favorites`

- **Body**:
  - `object` (string): `area|region|city|settlement|street|house`
  - `id` (number)
- **Успех 204**
- **Ошибка 406**: `{"error":"notAcceptable"}`

# `/api/addresses/favorites` — избранное

Реализация: `server/api/addresses/favorites.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/favorites.php` → `\api\addresses\favorites`.
- **Backend’и**:
  - backend `addresses`: `getFavorites()`, `addFavorite(object,id,title,icon,color)`, `deleteFavorite(object,id)`.
- **Хранилище (internal-variant)**:
  - таблица `addresses_favorites`.
  - Важно: в backend’е есть два режима удаления:
    - пользовательское удаление удаляет только для текущего `login`
    - “cleanup” удаление при удалении объекта адреса удаляет избранное для всех пользователей (`all=true`)
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` (закладки в сайдбаре и toggle favorite).

## GET `/api/addresses/favorites`

- **Успех 200**: `{"favorites":[ ... ]}`

## POST `/api/addresses/favorites`

- **Body**:
  - `object` (string): один из `area`, `region`, `city`, `settlement`, `street`, `house`
  - `id` (number)
  - `title` (string)
  - `icon` (string)
  - `color` (string)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## DELETE `/api/addresses/favorites`

- **Body**:
  - `object` (string)
  - `id` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

