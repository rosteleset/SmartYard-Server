# Вариант: `internal` (`server/backends/authorization/internal/internal.php`)

## Назначение

Authorization backend на базе БД.

Этот variant реализует:

- решения allow/deny на основе проиндексированных API-методов и сохранённых прав
- чтение/запись прав пользователей и групп
- вычисление списка доступных методов для пользователя

## Ключевые понятия

Авторизация завязана на идентификатор метода **AID** из `core_api_methods`, который создаётся через `reindex()`.

Специальные “корзины”:

- **common** методы (`core_api_methods_common`)
- **personal** методы (`core_api_methods_personal`) — разрешаются, когда `params["_id"] == uid`
- **by-backend** методы (`core_api_methods_by_backend`) — делегируются в другой backend через его `allow()`
- **permissions_same** — алиас прав (`#same(...)` в `index()` API-метода)

## `allow($params)`

Основная функция принятия решения.

Поведение по шагам (в общих чертах):

- Всегда разрешает `authentication/login`.
- Отклоняет, если `params["_uid"]` не является целым.
- Разрешает алиасы `permissions_same`: если метод “ссылается” на другой метод по правам, проверка идёт по целевой тройке.
- Если метод помечен `by_backend`, делегирует проверку в тот backend:
  - `loadBackend(<backend>)->allow($_params)`
- Админ (`uid === 0`) всегда разрешён.
- Иначе проверяет доступность AID по:
  - group allow (+ common методы)
  - user allow
  - минус group/user deny
- Для personal-методов дополнительно разрешает, когда `params["_id"] == uid` и метод есть в `core_api_methods_personal`.

## `allowedMethods($uid)`

Возвращает карту методов, доступных пользователю:

- `{ api: { method: { request_method: aid } } }`

Логика:

- admin (`uid === 0`) → `methods()` (все методы)
- иначе собирает список из БД:
  - group allow + common + personal + by-backend
  - минус group/user deny
  - плюс явный user allow
  - затем добавляет методы, у которых `permissions_same` указывает на разрешённые AID

Кеширование:

- кешируется под ключом `ALLOWED:<uid>`.

## Управление правами

- `getRights()` возвращает строки прав для `users` и `groups`.
- `setRights(...)` очищает кеш backend’а и переписывает allow/deny строки для пары `(uid|gid, api, method)`.
- `capabilities()` возвращает `["mode" => "rw"]`, что включает `api/authorization/rights`.

## Связанный код

- Reindex: `server/utils/reindex.php`
- API endpoints: `server/api/authorization/*`

