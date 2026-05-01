# Backend `authentication`

## Назначение

Аутентификация пользователей: проверка пароля, выдача токенов в Redis, логаут, работа с сессиями. Базовый класс содержит реализацию `login()` поверх `checkAuth()`.

## Код

- **Базовый класс**: `server/backends/authentication/authentication.php`.
- **Варианты**: `internal`, `external`.

## Конфигурация

Ключ в `server/config/config.json`: секция `backends.authentication` (TTL токенов, лимит сессий, 2FA и т.д.).

## Основные методы (контракт)

Контракт: `checkAuth(...)`; также методы базового класса для login/token/logout (см. файл).

## Кто использует

Accounts API (`/api/authentication/*`), точки входа Web/Mobile.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

