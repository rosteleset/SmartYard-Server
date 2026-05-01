# API `notes` (`server/api/notes/`)

## Назначение

Пользовательские заметки в Web UI: список, поиск, порядок.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/notes`) |
|------|----------------------------------------|
| `check.php` | `/check` |
| `note.php` | `/note` |
| `notes.php` | `/notes` |
| `reorder.php` | `/reorder` |
| `search.php` | `/search` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).