# `/api/cameras/cameras` — список камер, моделей, серверов и дерева

Реализация: `server/api/cameras/cameras.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Право привязано к `GET /api/addresses/house/:houseId` через `#same(addresses,house,GET)` в `index()`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/cameras/cameras.php` → класс `\api\cameras\cameras`.
- **Backend’и**:
  - backend `cameras`: `getCameras(by, query, true)`
  - backend `configs`: `getCamerasModels()`
  - backend `frs` (опционально): `servers()`
  - backend `households` (опционально): `getTree()`
- **Форма ответа**:
  - возвращается `{"cameras": { "cameras": [...], "models": [...], "frsServers": [...], "tree": ... }}`

## GET `/api/cameras/cameras`

### Query-параметры

- `by` (string, опционально)
- `query` (string, опционально)

### Ответы

- **Успех 200**: `{"cameras": { ... }}`

