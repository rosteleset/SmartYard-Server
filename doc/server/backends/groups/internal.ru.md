# Backend `groups` — вариант internal

Реализация: `server/backends/groups/internal/internal.php` (`backends\groups\internal`).

## Назначение

Хранит группы и членство в **PostgreSQL** (таблицы `core_*`), использует **кеш базового класса** backend’а (`CACHE:GROUPS:…`) и in-memory поля `$allGroups` / `$groupsByUid`.

## Таблицы

- **`core_groups`** — группа: `gid`, `acronym`, `name`, `admin` (UID администратора группы).
- **`core_users_groups`** — членство: пары `(uid, gid)`.

Дополнительно при подсчёте «пользователей в группе» и составе группы учитываются:

- администратор группы (`core_groups.admin`);
- первичная группа пользователя (`core_users.primary_group`).

## Кеширование

Ключи (поверх префикса `CACHE:GROUPS:` из базового класса, см. [backend.ru.md](../backend.ru.md)):

- `GROUPS` — полный список групп (без фильтра по uid);
- `GROUPSBY:<uid>` — группы, видимые пользователю (членство, primary_group, или он admin);
- `GROUP:<gid>` — одна группа;
- `USERS:<gid>` — массив UID участников.

После изменений данных вызывается `clearCache()` экземпляра; при `setUsers` / `setGroups` дополнительно вызывается глобальный `clearCache(...)` для согласованности кешей приложения.

## Поведение (выборочно)

- Числовые идентификаторы проверяются через `checkInt(...)`.
- `capabilities()` возвращает `{"mode":"rw"}` — Accounts API разрешает изменение состава, если mode именно `rw` (см. `groupUsers.php`, `group.php`).
- **`cleanup()`**: удаляет «висячие» строки в `core_users_rights`, `core_groups_rights`, `core_users_groups` (ссылки на несуществующие методы API, пользователей или группы). Вызывается из **`cron($part)`** каждые **5 минут** (`$part == "5min"`).

## Связанный HTTP API

- `GET /api/accounts/groups` — `getGroups(false)`
- `/api/accounts/group`, `/api/accounts/groupUsers` — операции над одной группей и пользователями в ней
