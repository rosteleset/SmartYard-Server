# Accounts (`accounts/*`)

This section documents the **accounts-related endpoints**:

- API endpoints under `server/api/accounts/*` (mounted under `/api/accounts/...`)
- a special **public** endpoint `/accounts/forgot` implemented in `server/utils/forgot.php` and dispatched directly by `server/frontend.php`

## Index

- [`/api/accounts/user` — user CRUD](./user.md)
- [`/api/accounts/users` — users list](./users.md)
- [`/api/accounts/group` — group CRUD](./group.md)
- [`/api/accounts/groups` — groups list](./groups.md)
- [`/api/accounts/groupUsers` — group membership](./groupUsers.md)
- [`/accounts/forgot` — password reset (public)](./forgot.md)

