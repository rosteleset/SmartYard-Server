# Backend `authorization`

Базовый класс: `server/backends/authorization/authorization.php` (`backends\authorization\authorization`).

Конкретные реализации находятся в `server/backends/authorization/<variant>/...` (например `server/backends/authorization/internal/internal.php`).

## Назначение

Управляет доступом к API методам и предоставляет:

- список всех API методов на сервере (`methods()`)
- управление правами (`getRights()`, `setRights()`)
- доступные методы для пользователя (`allowedMethods(uid)`)

`server/frontend.php` проверяет права, вызывая `authorization->allow($params)` перед dispatch API endpoint’а.

## Зависимости

- **Точки входа / вызывающие**:
  - `server/frontend.php` вызывает `authorization->allow($params)` чтобы решить, разрешён ли запрос.
  - API endpoint’ы `server/api/authorization/*` вызывают:
    - `allowedMethods(uid)` (`/api/authorization/available`)
    - `methods(all)` (`/api/authorization/methods`)
    - `getRights()` / `setRights(...)` (`/api/authorization/rights`)
  - UI использует `/api/authorization/available` чтобы строить `AVAIL(...)` (пункты меню/кнопки/фичи).
- **Хранилище (DB)**:
  - базовая реализация `methods($_all)` читает `core_api_methods` (и вспомогательные `core_api_methods_common`, `core_api_methods_by_backend`).
  - internal-variant использует таблицы прав (`core_users_rights`, `core_groups_rights`) для проверок и редактирования прав.
- **Хранилище (Redis)**:
  - использует backend cache из базового класса для результата `methods()`:
    - ключ выглядит как `CACHE:AUTHORIZATION:METHODS:<1|0>:<uid>` (через `cacheGet/cacheSet`)
- **Варианты**:
  - `server/backends/authorization/allow/allow.php` — allow-all вариант:
    - `allow()` всегда возвращает true
    - `allowedMethods(uid)` возвращает `methods()`
    - `capabilities()` возвращает false (редактирование прав не поддерживается)

## Публичный интерфейс

- `methods($_all = true)` (реализован в базовом классе)
- `getRights()` (abstract)
- `setRights($user, $id, $api, $method, $allow, $deny)` (abstract)
- `allowedMethods($uid)` (abstract)
- хелпер `mAllow($api, $method = false, $request_method = false)` для проверки доступности с текущими кредами

