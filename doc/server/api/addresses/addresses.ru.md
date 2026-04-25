# `/api/addresses/addresses` — получение иерархии адресов

Реализация: `server/api/addresses/addresses.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.
- Endpoint доступен только если существует backend `addresses` (`loadBackend("addresses")`).

## Зависимости

- **Точка входа / dispatch**: маршрутизируется в `server/frontend.php` через `server/api/<api>/<method>.php` и класс `\api\addresses\addresses`.
- **Backend’и**:
  - backend `addresses`:
    - методы списков: `getRegions()`, `getAreas(regionId)`, `getCities(regionId, areaId)`, `getSettlements(areaId, cityId)`, `getStreets(cityId, settlementId)`, `getHouses(settlementId, streetId)`
    - методы единичных объектов (когда передан фильтр `*Id`): `getArea(areaId)`, `getCity(cityId)`, `getSettlement(settlementId)`, `getStreet(streetId)`, `getHouse(houseId)`
- **Хранилище / side effects**:
  - GET 200 ответы могут кешироваться в Redis на уровне `server/frontend.php` (frontend cache).
- **Конфиг**:
  - у endpoint’а нет прямых зависимостей от config-ключей (кроме конфигурации backend’а).

## GET `/api/addresses/addresses`

Возвращает JSON-объект, содержащий одну или несколько коллекций адресной иерархии. Какие именно коллекции возвращаются — задаётся параметром `include`.

### Query-параметры

- `regionId` (number, опционально)
- `areaId` (number, опционально)
- `cityId` (number, опционально)
- `settlementId` (number, опционально)
- `streetId` (number, опционально)
- `houseId` (number, опционально)
- `include` (string, опционально): список коллекций через запятую.
  - по умолчанию: `regions,areas,cities,settlements,streets,houses`
  - распознаваемые значения (через `strpos()`): `regions`, `areas`, `cities`, `settlements`, `streets`, `houses`

### Примечания по поведению

- Каждая коллекция загружается отдельно, если её имя присутствует в `include`.
- Для `areas/cities/settlements/streets/houses`:
  - если передан соответствующий `*Id` (ненулевой), API возвращает массив из одного элемента: `[ getX(id) ]`
  - иначе возвращается список, отфильтрованный по parent id (например `getAreas(regionId)`, `getStreets(cityId, settlementId)`)

### Ответы

- **Успех 200**: `{"addresses": { "regions": [...], "areas": [...], ... }}`
- **Ошибка 400**: `{"error":"badRequest"}`
  - возвращается если цепочка вызовов backend’а возвращает `false` и handler делает `ANSWER(false, "badRequest")`

# `/api/addresses/addresses` — иерархические списки адресов

Реализация: `server/api/addresses/addresses.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/addresses.php` → `\api\addresses\addresses::GET()`.
- **Backend’и**:
  - backend `addresses` (`loadBackend("addresses")`): `getRegions()`, `getAreas()`, `getArea()`, `getCities()`, `getCity()`, `getSettlements()`, `getSettlement()`, `getStreets()`, `getStreet()`, `getHouses()`, `getHouse()`.
- **Хранилища**:
  - зависит от variant’а; internal-variant читает таблицы `addresses_*` (regions/areas/cities/settlements/streets/houses).
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` использует это как основной источник иерархии адресов.

## GET `/api/addresses/addresses`

Возвращает объект, который может включать: regions, areas, cities, settlements, streets, houses.

### Query-параметры

- `regionId` (number, опционально)
- `areaId` (number, опционально)
- `cityId` (number, опционально)
- `settlementId` (number, опционально)
- `streetId` (number, опционально)
- `houseId` (number, опционально)
- `include` (string, опционально, по умолчанию `"regions,areas,cities,settlements,streets,houses"`):
  список через запятую, который определяет какие коллекции включать.

### Успешный ответ

- **200**: `{"addresses": { "regions": [...], "areas": [...], ... }}`

Примечание:

- Если передан `*Id` для коллекции, возвращается массив из одного элемента (например `areas: [getArea(areaId)]`).

