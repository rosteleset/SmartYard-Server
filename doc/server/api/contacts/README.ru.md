# API `contacts` (`server/api/contacts/`)

## Назначение

Контакты: список и единичный контакт.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/contacts`) |
|------|----------------------------------------|
| `contact.php` | `/contact` |
| `contacts.php` | `/contacts` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).