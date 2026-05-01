# API `houses` (`server/api/houses/`)

## Назначение

Дома, подъезды, квартиры, домофоны, CMS, камеры объекта, поиск, автоконфигурация.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/houses`) |
|------|----------------------------------------|
| `autoconfigure.php` | `/autoconfigure` |
| `cameras.php` | `/cameras` |
| `cms.php` | `/cms` |
| `customFields.php` | `/customFields` |
| `customFieldsConfiguration.php` | `/customFieldsConfiguration` |
| `domophone.php` | `/domophone` |
| `domophones.php` | `/domophones` |
| `entrance.php` | `/entrance` |
| `flat.php` | `/flat` |
| `flats.php` | `/flats` |
| `house.php` | `/house` |
| `leaf.php` | `/leaf` |
| `path.php` | `/path` |
| `search.php` | `/search` |
| `sharedEntrances.php` | `/sharedEntrances` |
| `watch.php` | `/watch` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).