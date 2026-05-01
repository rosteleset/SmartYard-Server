# `groups` backend — base interface

Base class: `server/backends/groups/groups.php` (`backends\groups\groups`).

## Purpose

Manages **user groups**: listing groups, membership, group admin (`admin` field), and the relationship between users and groups (membership table and user `primary_group`, depending on the variant).

## Configuration

In `server/config/config.json`, under `"backends"`, set the variant:

```json
"groups": {
    "backend": "internal"
}
```

See also the [backend loader](../../utils/loader.md) and the [`backend` base class](../backend.md).

## Callers

- **Accounts API** (`server/api/accounts/`):
  - `groups.php` — list all groups
  - `group.php` — single group CRUD
  - `groupUsers.php` — member UIDs and bulk membership replace
  - `user.php` — may call `setGroups($uid, $gids)` when saving a user
- **Other backends**: `users/internal`, `authentication/external`, `tt/internal`, `wg/internal` load groups for domain logic.

## Public contract (abstract methods)

| Method | Role |
|--------|------|
| `getGroups($uid = false)` | All groups, or groups tied to user `$uid` |
| `getGroup($gid)` | One group by `gid` |
| `getGroupByAcronym($acronym)` | Lookup by acronym |
| `addGroup($acronym, $name)` | Create group |
| `modifyGroup($gid, $acronym, $name, $admin)` | Update fields and admin |
| `deleteGroup($gid)` | Delete group |
| `getUsers($gid)` | Member UIDs |
| `setUsers($gid, $uids)` | Replace membership for the group |
| `setGroups($uid, $gids)` | Replace groups for the user |
| `deleteUser($uid)` | Remove user from all group membership rows |
| `addUserToGroup($uid, $gid)` | Add one uid↔gid link |

Implementations inherit caching and dependencies from `backends\backend`.
