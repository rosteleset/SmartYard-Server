# `/api/authentication/login` — вход

Реализация: `server/api/authentication/login.php`.

## Авторизация и права

- Endpoint вызывается без Bearer-токена.
- `server/frontend.php` обрабатывает `authentication/login` особым образом: проверяет, что переданы `login` и `password`, но не требует существующей авторизации.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/authentication/login.php` → класс `\api\authentication\login`.
- **Backend’и**: backend `authentication`:
  - `authentication->login(login, password, rememberMe, ua, did, ip, oneCode)`
- **Хранилище**:
  - `authentication->login()` сохраняет токены в Redis (ключи `AUTH:<token>:<uid>`; также может использовать `PERSISTENT:*` в зависимости от реализации).
- **Криптография**:
  - Если в Redis есть ключ `PK` и в запросе передан `encrypted=true`, пароль расшифровывается через `decryptData(password, pk)`.
- **Вызывается из UI**:
  - `client/js/app.js` отправляет запрос на `authentication/login` (опционально `encrypted: true` и `oneCode` для OTP).

## POST `/api/authentication/login`

### Body

- `login` (string)
- `password` (string)
- `rememberMe` (string/bool, опционально)
- `did` (string, опционально): device id (используется вместе с rememberMe)
- `oneCode` (string, опционально): OTP код
- `encrypted` (boolean, опционально): признак шифрованного пароля

### Ответы

- **Успех 200**:
  - требуется OTP: `{"otp": true}`
  - успех: `{"token": "<token>"}`
- **Ошибка**: код/ошибка пробрасываются из backend’а (например `404 {"error":"userNotFound"}`)

