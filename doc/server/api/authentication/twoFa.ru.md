# `/api/authentication/twoFa` — запрос/подтверждение 2FA

Реализация: `server/api/authentication/twoFa.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/authentication/twoFa.php` → класс `\api\authentication\twoFa`.
- **Backend’и**: backend `authentication`:
  - `twoFa(token, oneCode)`
- **Библиотеки**:
  - под капотом backend использует GoogleAuthenticator для OTP (см. `server/backends/authentication/authentication.php`).

## POST `/api/authentication/twoFa`

### Body

- `oneCode` (string, опционально): OTP код (для подтверждения)

### Ответы

- **Успех 200**: `{"twoFa": <boolean|object>}` (форма зависит от backend’а)
- **Ошибка 406**: `{"error":"notAcceptable"}`

