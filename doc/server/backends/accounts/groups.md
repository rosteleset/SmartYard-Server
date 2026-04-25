# `groups` backend

Base class: `server/backends/groups/groups.php` (`backends\groups\groups`).

Concrete implementations live under `server/backends/groups/<variant>/...` (for example `server/backends/groups/internal/internal.php`).

## Purpose

Manages groups and group membership. Used by:

- Accounts API (`/api/accounts/group`, `/api/accounts/groups`, `/api/accounts/groupUsers`)
- Users API updates that modify user group membership (`/api/accounts/user` can call `groups->setGroups()`)
- Authorization/rights cleanup routines in internal variants

## Dependencies

- **Entry points / callers**:
  - API endpoints:
    - `server/api/accounts/group.php`
    - `server/api/accounts/groups.php`
    - `server/api/accounts/groupUsers.php`
    - indirectly from `server/api/accounts/user.php` when `userGroups` is provided
- **Storage**:
  - **DB**: internal variant uses `core_groups` and mapping table `core_users_groups` (and related rights tables in cleanup)
  - **Redis**:
    - backend cache via base backend helper: `CACHE:GROUPS:<key>:<uid>`
- **Side effects**:
  - internal variant clears backend cache on membership changes (e.g. `addUserToGroup()` calls `clearCache()`)
  - internal variant runs cleanup periodically from `cron("5min")`
- **Capabilities**:
  - API endpoints expose write methods only when `groups->capabilities()["mode"] === "rw"`

## Public interface (base class)

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

