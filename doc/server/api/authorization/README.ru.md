# API `authorization` (`server/api/authorization/`)

## Назначение

Матрица прав на методы API, доступные методы для текущего пользователя, массовые права.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/authorization`) |
|------|----------------------------------------|
| `available.php` | `/available` |
| `methods.php` | `/methods` |
| `rights.php` | `/rights` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).

---

## Подробная документация

## Содержание

- [`available.php`](./available.ru.md) — `GET /api/authorization/available`
- [`methods.php`](./methods.ru.md) — `GET /api/authorization/methods`
- [`rights.php`](./rights.ru.md) — `GET/POST /api/authorization/rights`