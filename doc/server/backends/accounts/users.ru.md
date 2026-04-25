# Backend `users`

Базовый класс: `server/backends/users/users.php` (`backends\users\users`).

Конкретные реализации находятся в `server/backends/users/<variant>/...` (например `server/backends/users/internal/internal.php`).

## Назначение

Отвечает за управление пользователями и вспомогательные операции, которые используются:

- Accounts API (`/api/accounts/user`, `/api/accounts/users`)
- Backend `authentication` для проверки 2FA и валидации идентичности при логине
- Сценарий восстановления пароля (`/accounts/forgot`)
- UI-страницы, которые загружают список пользователей и карточки пользователей

## Зависимости

- **Точки входа / вызывающие**:
  - API endpoint’ы:
    - `server/api/accounts/user.php`
    - `server/api/accounts/users.php`
    - `server/utils/forgot.php` (`/accounts/forgot`)
  - Другие backend’и:
    - `server/backends/authentication/authentication.php` вызывает `users->twoFa()` и `users->getUidByLogin()`
  - Диспетчер:
    - `server/frontend.php` инициализирует backend’и и передаёт их в `$params["_backends"]`
- **Хранилища**:
  - **DB**: internal-variant использует таблицу `core_users` (и связанные `core_users_groups`, `core_users_rights`)
  - **Redis**:
    - участвует в auth/session сценариях через ключи `AUTH:*:<uid>` и `PERSISTENT:<token>:<uid>` (см. backend `authentication` и internal-variant users)
    - использует backend cache из базового класса: `CACHE:USERS:<key>:<uid>`
- **Конфиг**:
  - в конструкторе базового `users` создаётся ClickHouse-подключение из `$config["clickhouse"]` (при отсутствии берутся дефолты)
  - уведомления в internal-variant используют `$config["telegram"]["bot"]` и `$config["email"]`
- **Внешние сервисы**:
  - Telegram Bot API вызывается напрямую из `sendTg()` через `file_get_contents("https://api.telegram.org/bot...")`
  - Email отправляется через хелпер `eMail()` из `server/utils/email.php`

## Публичный интерфейс (базовый класс)

Базовый класс определяет методы, которые реализует конкретный variant:

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

Полный список сигнатур и хелперы — в `server/backends/users/users.php`.

