# `users` backend

Base class: `server/backends/users/users.php` (`backends\users\users`).

Concrete implementations live under `server/backends/users/<variant>/...` (for example `server/backends/users/internal/internal.php`).

## Purpose

Provides user management and user-related helper operations used by:

- Accounts API (`/api/accounts/user`, `/api/accounts/users`)
- Authentication (`backends/authentication/authentication.php`) for 2FA checks and login identity validation
- Password reset flow (`/accounts/forgot`)
- Various UI pages that load user lists and user details

## Dependencies

- **Entry points / callers**:
  - API endpoints:
    - `server/api/accounts/user.php`
    - `server/api/accounts/users.php`
    - `server/utils/forgot.php` (`/accounts/forgot`)
  - Other backends:
    - `server/backends/authentication/authentication.php` calls `users->twoFa()` and `users->getUidByLogin()`
  - Dispatcher:
    - `server/frontend.php` initializes backends and injects them into `$params["_backends"]`
- **Storage**:
  - **DB**: internal variant uses `core_users` (and related tables such as `core_users_groups`, `core_users_rights`)
  - **Redis**:
    - participates in auth/session flows via keys like `AUTH:*:<uid>` and `PERSISTENT:<token>:<uid>` (see `authentication` backend and internal users variant)
    - uses backend cache keys via base backend helper: `CACHE:USERS:<key>:<uid>`
- **Config**:
  - `clickhouse` connection is created in the base `users` constructor using `$config["clickhouse"]` (defaults are hardcoded if not present)
  - notification helpers use `$config["telegram"]["bot"]` and `$config["email"]` (in internal variant)
- **External services**:
  - Telegram Bot API is called directly by `sendTg()` via `file_get_contents("https://api.telegram.org/bot...")`
  - Email is sent via `eMail()` helper from `server/utils/email.php`

## Public interface (base class)

The base class defines core methods that concrete variants implement:

- `getUsers($withSessions = false, $withLast = false)`
- `getUser($uid, $withGroups = true)`
- `getUidByEMail($eMail)`
- `getUidByLogin($login)`
- `getLoginByUid($uid)`
- `addUser($login, $realName = null, $eMail = null, $phone = null)`
- `setPassword($uid, $password)`
- `deleteUser($uid)`
- `modifyUser(...)`
- `userPersonal(...)`
- `twoFa($uid, $secret = "")`

See `server/backends/users/users.php` for the full signature list and helper methods.

