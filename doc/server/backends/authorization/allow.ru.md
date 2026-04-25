# Вариант: `allow` (`server/backends/authorization/allow/allow.php`)

## Назначение

Authorization backend “разрешить всё”.

Этот variant:

- всегда возвращает `true` из `allow($params)`
- считает доступными все проиндексированные методы
- **не** поддерживает управление правами (read-only)

## Поведение

- `allow($params)` → `true`
- `allowedMethods($uid)` → `methods()` (все методы)
- `getRights()` / `setRights(...)` → `false` (заглушки)
- `capabilities()` → `false` (нет `rw` режима)

## Примечания

Т.к. нет `capabilities()["mode"] === "rw"`, endpoint `api/authorization/rights` считается выключенным логикой индексации.

