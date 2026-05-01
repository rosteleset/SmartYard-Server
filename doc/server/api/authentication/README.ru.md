# API `authentication` (`server/api/authentication/`)

## Назначение

Вход в систему, выход, настройка двухфакторной аутентификации.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/authentication`) |
|------|----------------------------------------|
| `login.php` | `/login` |
| `logout.php` | `/logout` |
| `twoFa.php` | `/twoFa` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).

---

## Подробная документация

## Содержание

- [`/api/authentication/login`](./login.ru.md)
- [`/api/authentication/logout`](./logout.ru.md)
- [`/api/authentication/twoFa`](./twoFa.ru.md)