# API `addresses` (`server/api/addresses/`)

## Назначение

Адресная иерархия (регион → … → дом), поиск, избранное.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/addresses`) |
|------|----------------------------------------|
| `addresses.php` | `/addresses` |
| `area.php` | `/area` |
| `city.php` | `/city` |
| `favorites.php` | `/favorites` |
| `house.php` | `/house` |
| `region.php` | `/region` |
| `search.php` | `/search` |
| `settlement.php` | `/settlement` |
| `street.php` | `/street` |

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).

---

## Подробная документация

## Содержание

- [`/api/addresses/addresses` — получение иерархии адресов](./addresses.ru.md)
- [`/api/addresses/region` — CRUD региона](./region.ru.md)
- [`/api/addresses/area` — CRUD района/области](./area.ru.md)
- [`/api/addresses/city` — CRUD города](./city.ru.md)
- [`/api/addresses/settlement` — CRUD населённого пункта](./settlement.ru.md)
- [`/api/addresses/street` — CRUD улицы](./street.ru.md)
- [`/api/addresses/house` — CRUD дома](./house.ru.md)
- [`/api/addresses/search` — поиск адресов](./search.ru.md)
- [`/api/addresses/favorites` — избранное](./favorites.ru.md)

# Addresses (`addresses/*`)

В этом разделе описаны API endpoint’ы для адресов, реализованные в `server/api/addresses/*`.

## Авторизация и права

- Все endpoint’ы требуют `Authorization: Bearer <token>`.
- Запросы маршрутизируются в `server/frontend.php` и допускаются/запрещаются через `authorization->allow($params)`.
- Многие write-endpoint’ы (и `search`) явно переиспользуют модель прав `/api/addresses/house` через `#same(addresses,house,...)`.

## Содержание

- [`/api/addresses/addresses` — иерархические списки адресов](./addresses.ru.md)
- [`/api/addresses/search` — поиск адресов](./search.ru.md)
- [`/api/addresses/house` — CRUD домов (+ magic create)](./house.ru.md)
- [`/api/addresses/region` — CRUD регионов](./region.ru.md)
- [`/api/addresses/area` — CRUD районов/областей](./area.ru.md)
- [`/api/addresses/city` — CRUD городов](./city.ru.md)
- [`/api/addresses/settlement` — CRUD населённых пунктов](./settlement.ru.md)
- [`/api/addresses/street` — CRUD улиц](./street.ru.md)
- [`/api/addresses/favorites` — избранное](./favorites.ru.md)