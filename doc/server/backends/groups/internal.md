# `groups` backend — internal variant

Implementation: `server/backends/groups/internal/internal.php` (`backends\groups\internal`).

## Purpose

Stores groups and membership in **PostgreSQL** (`core_*` tables), uses the **base backend cache** (`CACHE:GROUPS:…`) plus in-memory `$allGroups` / `$groupsByUid`.

## Tables

- **`core_groups`** — `gid`, `acronym`, `name`, `admin` (group admin UID).
- **`core_users_groups`** — membership rows `(uid, gid)`.

When counting users in a group or listing members, the implementation also considers:

- the group admin (`core_groups.admin`);
- the user’s primary group (`core_users.primary_group`).

## Caching

Keys (on top of the `CACHE:GROUPS:` prefix from the base class; see [backend.md](../backend.md)):

- `GROUPS` — full group list (no uid filter);
- `GROUPSBY:<uid>` — groups visible to that user (membership, primary group, or admin);
- `GROUP:<gid>` — one group;
- `USERS:<gid>` — member UID list.

After writes, the instance calls `clearCache()`; `setUsers` / `setGroups` also call the global `clearCache(...)` for app-wide cache coherence.

## Behaviour notes

- Numeric IDs are validated with `checkInt(...)`.
- `capabilities()` returns `{"mode":"rw"}` — Accounts API allows membership edits only when mode is `rw` (see `groupUsers.php`, `group.php`).
- **`cleanup()`** removes stale rows in `core_users_rights`, `core_groups_rights`, and `core_users_groups` (orphaned references). Invoked from **`cron($part)`** on **`5min`** (`$part == "5min"`).

## Related HTTP API

- `GET /api/accounts/groups` — `getGroups(false)`
- `/api/accounts/group`, `/api/accounts/groupUsers` — single-group CRUD and member lists
