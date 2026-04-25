# Backend `groups`

Базовый класс: `server/backends/groups/groups.php` (`backends\groups\groups`).

Конкретные реализации находятся в `server/backends/groups/<variant>/...` (например `server/backends/groups/internal/internal.php`).

## Назначение

Управляет группами и составом групп. Используется:

- Accounts API (`/api/accounts/group`, `/api/accounts/groups`, `/api/accounts/groupUsers`)
- Обновлением пользователя, когда меняются его группы (`/api/accounts/user` может вызвать `groups->setGroups()`)
- Cleanup-рутинами (в internal-variant) для прав/связей

## Зависимости

- **Точки входа / вызывающие**:
  - API endpoint’ы:
    - `server/api/accounts/group.php`
    - `server/api/accounts/groups.php`
    - `server/api/accounts/groupUsers.php`
    - косвенно из `server/api/accounts/user.php`, если передан `userGroups`
- **Хранилища**:
  - **DB**: internal-variant использует `core_groups` и таблицу связей `core_users_groups` (а также таблицы прав в cleanup)
  - **Redis**:
    - backend cache из базового класса: `CACHE:GROUPS:<key>:<uid>`
- **Side effects**:
  - internal-variant чистит backend cache при изменении состава (например `addUserToGroup()` вызывает `clearCache()`)
  - internal-variant запускает cleanup из `cron("5min")`
- **Capabilities**:
  - API endpoint’ы публикуют write-методы только если `groups->capabilities()["mode"] === "rw"`

## Публичный интерфейс (базовый класс)

- `getGroups($uid = false)`
- `getGroup($gid)`
- `getGroupByAcronym($acronym)`
- `modifyGroup($gid, $acronym, $name, $admin)`
- `addGroup($acronym, $name)`
- `deleteGroup($gid)`
- `getUsers($gid)`
- `setUsers($gid, $uids)`
- `setGroups($uid, $gids)`
- `deleteUser($uid)`
- `addUserToGroup($uid, $gid)`

