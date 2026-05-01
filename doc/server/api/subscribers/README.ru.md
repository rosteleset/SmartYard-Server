# API `subscribers` (`server/api/subscribers/`)

## Назначение

Абоненты, устройства, ключи, поиск, камеры квартиры.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/subscribers`) |
|------|----------------------------------------|
| `device.php` | `/device` |
| `devices.php` | `/devices` |
| `flatCameras.php` | `/flatCameras` |
| `key.php` | `/key` |
| `keys.php` | `/keys` |
| `search.php` | `/search` |
| `searchFlat.php` | `/searchFlat` |
| `searchRf.php` | `/searchRf` |
| `subscriber.php` | `/subscriber` |
| `subscriberCameras.php` | `/subscriberCameras` |
| `subscribers.php` | `/subscribers` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).