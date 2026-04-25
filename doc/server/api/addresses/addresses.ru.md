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

