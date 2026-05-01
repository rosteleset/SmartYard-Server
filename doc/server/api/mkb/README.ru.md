# API `mkb` (`server/api/mkb/`)

## Назначение

Kanban-доски и карточки (MKB), отправка, чужие доски.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/mkb`) |
|------|----------------------------------------|
| `card.php` | `/card` |
| `cards.php` | `/cards` |
| `desk.php` | `/desk` |
| `desks.php` | `/desks` |
| `otherCards.php` | `/otherCards` |
| `otherDesks.php` | `/otherDesks` |
| `send.php` | `/send` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).