# Accounts (`accounts/*`)

В этом разделе описаны **endpoint’ы, связанные с аккаунтами**:

- API endpoint’ы из `server/api/accounts/*` (доступны как `/api/accounts/...`)
- отдельный **публичный** endpoint `/accounts/forgot`, реализованный в `server/utils/forgot.php` и вызываемый напрямую из `server/frontend.php`

## Содержание

- [`/api/accounts/user` — CRUD пользователя](./user.ru.md)
- [`/api/accounts/users` — список пользователей](./users.ru.md)
- [`/api/accounts/group` — CRUD группы](./group.ru.md)
- [`/api/accounts/groups` — список групп](./groups.ru.md)
- [`/api/accounts/groupUsers` — состав группы](./groupUsers.ru.md)
- [`/accounts/forgot` — восстановление пароля (публичный)](./forgot.ru.md)

