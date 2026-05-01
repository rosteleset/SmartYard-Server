# Backend `users`

## Назначение

Учётные записи операторов: CRUD пользователей, пароли, группы, права, сессии; конструктор подключает ClickHouse для части аналитики.

## Код

- **Базовый класс**: `server/backends/users/users.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.users`.

## Основные методы (контракт)

`getUsers`, `getUser`, `getUidByLogin`, `addUser`, `setPassword`, и дальше по файлу.

## Кто использует

Базовый класс `backend` (разрешение uid), authentication, Accounts API, TT.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

