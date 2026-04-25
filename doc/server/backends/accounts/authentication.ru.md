# Backend `authentication`

Базовый класс: `server/backends/authentication/authentication.php` (`backends\authentication\authentication`).

Конкретные реализации находятся в `server/backends/authentication/<variant>/...`.

## Назначение

Отвечает за:

- аутентификацию (`login`)
- проверку токенов (`auth`)
- жизненный цикл сессий/токенов (`logout`)
- 2FA проверку (`twoFa`)

## Зависимости

- **Точки входа / вызывающие**:
  - API endpoint’ы (маршрутизируются через `server/frontend.php`) вызывают `authentication` для login/logout и проверки токена.
  - `server/frontend.php` вызывает `authentication->auth()` для большинства API вызовов, чтобы определить текущего пользователя.
  - Accounts API может вызвать `authentication->logout()` через `/api/accounts/user/:uid` DELETE, если передан `session`.
- **Backend’и**:
  - backend `users`:
    - `users->twoFa(uid)` используется в `login()` чтобы понять, нужен ли OTP
    - `users->getUidByLogin(login)` используется в `auth()` чтобы убедиться, что `login` всё ещё соответствует `uid`
- **Хранилище / side effects (Redis)**:
  - сессионные токены:
    - `AUTH:<token>:<uid>`: основной токен (TTL зависит от persistent и `token_idle_ttl`)
    - `PERSISTENT:<token>:<uid>`: persistent-токен (используется при rememberMe / persistent tokens)
  - sudo-режим:
    - `SUDO:<login>`: если ключ есть, `auth()` временно мапит пользователя на uid `0` и возвращает `sudoed` как TTL.
  - служебные метки:
    - `LAST:LOGIN:<md5(login)>`
- **Зависимости от конфига**:
  - `config["backends"]["authentication"]["max_allowed_tokens"]` (по умолчанию 15)
  - `config["backends"]["authentication"]["token_idle_ttl"]` (по умолчанию 3600)
- **Библиотеки**:
  - `lib/GoogleAuthenticator/GoogleAuthenticator.php` используется для проверки OTP.

## Примечания по поведению

- `login()` удаляет старые токены, если активных токенов на uid слишком много.
- Токены формируются на основе MD5 (в некоторых режимах) либо через GUID+MD5.
- `auth()` поддерживает:
  - `Authorization: Bearer <token>`
  - `Authorization: Base64 <b64(login)> <b64(password)>` (опционально header `X-Otp` для OTP)

