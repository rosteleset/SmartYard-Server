# `/api/addresses/search` — поиск адресов

Реализация: `server/api/addresses/search.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/search.php` → `\api\addresses\search::GET()`.
- **Backend’и**:
  - backend `addresses`: `searchAddress(search)`.
    - Примечание: в internal-variant `searchAddress()` сейчас реализован как `return [];` (пустые результаты).
- **Связка прав**:
  - `index()` объявляет `GET => #same(addresses,house,GET)`, то есть доступ контролируется так же, как `GET /api/addresses/house/:houseId`.
- **Вызывается из UI**:
  - UI адресов использует поиск (подмодуль `_search` в `client/modules/addresses/`).

## GET `/api/addresses/search`

- **Query**: `search` (string)
- **Успех 200**: `{"addresses":[ ... ]}`
- **Успех 204**: пустое тело (если backend вернул `false`, базовый API закодирует `204`; зависит от реализации backend’а)

