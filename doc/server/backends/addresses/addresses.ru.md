# Backend `addresses` (обзор)

Базовый класс: `server/backends/addresses/addresses.php` (`backends\addresses\addresses`).

Конкретные реализации находятся в `server/backends/addresses/<variant>/...` (например `server/backends/addresses/internal/internal.php`).

## Назначение

Предоставляет хранение и операции над иерархией адресов, используемые:

- Addresses API (`server/api/addresses/*`)
- UI адресов (`client/modules/addresses/*`)

## Зависимости

- **Точки входа / вызывающие**:
  - API endpoint’ы:
    - `server/api/addresses/addresses.php`
    - `server/api/addresses/search.php`
    - `server/api/addresses/house.php`
    - `server/api/addresses/region.php`
    - `server/api/addresses/area.php`
    - `server/api/addresses/city.php`
    - `server/api/addresses/settlement.php`
    - `server/api/addresses/street.php`
    - `server/api/addresses/favorites.php`
  - UI:
    - `client/modules/addresses/addresses.js` (весь CRUD + favorites)
    - `client/modules/addresses/houses.js` (использует “magic” создание дома)
- **Хранилище (internal-variant)**:
  - **DB таблицы**:
    - `addresses_regions`
    - `addresses_areas`
    - `addresses_cities`
    - `addresses_settlements`
    - `addresses_streets`
    - `addresses_houses`
    - `addresses_favorites`
  - **Redis ключи**:
    - `house_<uuid>`: JSON, который читает `addHouseByMagic(uuid)`
    - backend cache из базового класса: `CACHE:ADDRESSES:<key>:<uid>`
- **Конфиг**:
  - `config["backends"]["addresses"]["text_search_mode"]` влияет на `searchHouse()` в internal-variant.
  - `config["db"]["text_search_config"]` используется для PostgreSQL full-text search.
- **Side effects / обслуживание**:
  - internal-variant запускает referential `cleanup()`:
    - после удалений объектов адреса
    - периодически из `cron("5min")`
  - при удалении объекта адреса также удаляется избранное для этого объекта у всех пользователей (`deleteFavorite(object, id, all=true)`).

## Примечания

- В internal-variant `searchAddress()` сейчас возвращает пустой массив, при этом `searchHouse()` реализован и поддерживает разные стратегии поиска (в зависимости от типа БД и конфига).
- “Magic” создание дома (`addHouseByMagic`) ожидает, что какой-то upstream компонент положит данные в Redis ключ `house_<uuid>`.

