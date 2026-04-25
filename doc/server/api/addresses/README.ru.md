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

