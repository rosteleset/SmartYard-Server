# Backend `addresses` — internal variant

Реализация: `server/backends/addresses/internal/internal.php` (`backends\addresses\internal`).

## Назначение

Хранит и отдаёт адресную иерархию из БД проекта.

## Зависимости

- **Хранилище (DB)**:
  - Использует таблицы (видно из SQL-запросов в реализации):
    - `addresses_regions`
    - `addresses_areas`
    - (и дальнейшие таблицы семейства `addresses_*` для городов/НП/улиц/домов)
- **Хранилище (Redis)**:
  - Может использовать backend cache из базового класса (`CACHE:ADDRESSES:*`) там, где variant кеширует результаты.
- **Вызывается сверху**:
  - API endpoint `server/api/addresses/addresses.php` зависит от:
    - `getRegions()`, `getAreas()`, `getCities()`, `getSettlements()`, `getStreets()`, `getHouses()`
    - и методов единичных объектов `getArea(id)`, `getCity(id)` и т.п.
  - Импорт из биллинга (`backends\billing\billing::importAddressHierarchy()`) может вызывать add/modify методы для upsert иерархии.

## Примечания по поведению

- Методы валидируют числовые id через `checkInt(...)` перед SQL.
- Удаления могут запускать cleanup и удалять связанные “избранные” (например `deleteFavorite('region', ...)`).
- `capabilities()` возвращает `{"mode":"rw"}` (internal поддерживает запись).

