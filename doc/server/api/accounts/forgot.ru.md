# `/accounts/forgot` — восстановление пароля (публичный)

Реализация: `server/utils/forgot.php`, специальный dispatch в `server/frontend.php`.

## Почему это особый endpoint

`/accounts/forgot` **не** реализован как обычный endpoint `server/api/<api>/<method>.php`:

- в `server/frontend.php` есть исключение, которое **не требует Bearer-токен** для `accounts/forgot`
- запрос обрабатывается прямым вызовом `forgot($params)` (без проверки `authorization->allow()`)

Это нужно, потому что UI вызывает его без токена (например, чтобы понять, показывать ли ссылку “Забыли пароль”).

## GET `/accounts/forgot`

Поведение зависит от query-параметров.

### `?available=ask`

Используется UI как “пробник” доступности.

- Возвращает **403**, если:
  - backend `users` не в режиме `rw`, **или**
  - email не настроен (`!$config["email"]`)
- Иначе возвращает **204**.

### `?eMail=<email>`

Выдаёт короткоживущий токен и отправляет письмо, если:

- email принадлежит существующему пользователю (`users->getUidByEMail()`), и
- в Redis нет активного ключа `FORGOT:*:<uid>`.

Детали реализации:

- ключ токена: `FORGOT:<token>:<uid>`
- ttl: 900 секунд
- письмо содержит ссылку `${config.api.frontend}/accounts/forgot?token=<token>`

Ответ всегда **204** (даже если пользователь не найден / уже есть активный токен).

### `?token=<token>`

Поглощает токен и меняет пароль:

- ищет ключи `FORGOT:<token>:*`, удаляет их и извлекает `uid`
- генерирует новый пароль и сохраняет его через `users->setPassword(uid, pw)`
- отправляет новый пароль на email пользователя
- инвалидирует сессии, удаляя Redis-ключи `AUTH:*:<uid>`

Поведение ответа:

- при успехе пишет plain text `check your mailbox for your new password` и делает `exit`
- иначе “проваливается” в **204**

