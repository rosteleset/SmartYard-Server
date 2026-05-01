# API `accounts` (`server/api/accounts/`)

## Назначение

Учётные записи операторов и группы: пользователи, группы, состав группы. Отдельно от маршрута `/api/…` поддержан публичный сценарий восстановления пароля — см. ниже в подробных страницах.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/accounts`) |
|------|----------------------------------------|
| `group.php` | `/group` |
| `groupUsers.php` | `/groupUsers` |
| `groups.php` | `/groups` |
| `user.php` | `/user` |
| `users.php` | `/users` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).

---

## Подробная документация

## Содержание

- [`/api/accounts/user` — CRUD пользователя](./user.ru.md)
- [`/api/accounts/users` — список пользователей](./users.ru.md)
- [`/api/accounts/group` — CRUD группы](./group.ru.md)
- [`/api/accounts/groups` — список групп](./groups.ru.md)
- [`/api/accounts/groupUsers` — состав группы](./groupUsers.ru.md)
- [`/accounts/forgot` — восстановление пароля (публичный)](./forgot.ru.md)