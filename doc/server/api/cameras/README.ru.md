# API `cameras` (`server/api/cameras/`)

## Назначение

Реестр камер, единичная камера, снимок (camshot).

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/cameras`) |
|------|----------------------------------------|
| `camera.php` | `/camera` |
| `cameras.php` | `/cameras` |
| `camshot.php` | `/camshot` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).

---

## Подробная документация

## Содержание

- [`/api/cameras/cameras` — список камер + модели + FRS сервера + дерево](./cameras.ru.md)
- [`/api/cameras/camera` — CRUD камеры](./camera.ru.md)
- [`/api/cameras/camshot` — снимок`](./camshot.ru.md)