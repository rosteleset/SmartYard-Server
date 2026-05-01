# Backend `groups` — базовый интерфейс

Базовый класс: `server/backends/groups/groups.php` (`backends\groups\groups`).

## Назначение

Управление сущностью «группа»: список групп, участники, администратор группы (поле `admin`), связь с пользователями через таблицу членства и через `primary_group` у пользователя (конкретика зависит от variant).

## Конфигурация

В `server/config/config.json` в секции `"backends"` задаётся variant:

```json
"groups": {
    "backend": "internal"
}
```

См. также [загрузчик backend’ов](../../utils/loader.ru.md) и [базовый класс `backend`](../backend.ru.md).

## Кто вызывает

- **Accounts API** (`server/api/accounts/`):
  - `groups.php` — список всех групп
  - `group.php` — одна группа, создание, изменение, удаление
  - `groupUsers.php` — UID в группе, массовая замена состава
  - `user.php` — при сохранении пользователя может вызываться `setGroups($uid, $gids)`
- **Другие backend’и**: `users/internal`, `authentication/external`, `tt/internal`, `wg/internal` — подгрузка групп для доменной логики.

## Публичный контракт (abstract-методы)

| Метод | Назначение |
|--------|------------|
| `getGroups($uid = false)` | Все группы или группы, доступные/связанные с пользователем `$uid` |
| `getGroup($gid)` | Одна группа по `gid` |
| `getGroupByAcronym($acronym)` | Поиск по акрониму |
| `addGroup($acronym, $name)` | Создание группы |
| `modifyGroup($gid, $acronym, $name, $admin)` | Изменение полей и администратора |
| `deleteGroup($gid)` | Удаление группы |
| `getUsers($gid)` | Список UID участников группы |
| `setUsers($gid, $uids)` | Замена состава членства в группе |
| `setGroups($uid, $gids)` | Замена групп пользователя |
| `deleteUser($uid)` | Убрать пользователя из членства во всех группах |
| `addUserToGroup($uid, $gid)` | Добавить одну связь uid↔gid |

Реализации наследуют кеш и зависимости от `backends\backend`.
