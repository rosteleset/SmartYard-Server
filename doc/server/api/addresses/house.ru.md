# `/api/addresses/house` — CRUD дома (+ magic create)

Реализация: `server/api/addresses/house.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/addresses/house.php` → класс `\api\addresses\house`.
- **Backend’и**:
  - backend `addresses`: `getHouse()`, `modifyHouse()`, `addHouse()`, `addHouseByMagic()`, `deleteHouse()`.
- **Хранилища**:
  - зависит от variant’а; internal-variant использует `addresses_houses` и может создавать связанные записи region/area/city/settlement/street при `addHouseByMagic()`.
  - internal-variant использует Redis-ключ `house_<uuid>` как вход для `addHouseByMagic()` (там ожидается JSON, который должен быть записан каким-то upstream процессом).
- **Side effects**:
  - internal-variant запускает referential cleanup после удалений и некоторых операций записи (`cleanup()`), а также периодически из `cron("5min")`.
- **Якорь прав**:
  - многие другие endpoint’ы используют `#same(addresses,house,<VERB>)`, то есть переиспользуют права этого endpoint’а как базовые (region/area/city/settlement/street CRUD и `/api/addresses/search`).
- **Вызывается из UI**:
  - `client/modules/addresses/addresses.js` (создание/изменение/удаление домов)
  - `client/modules/addresses/houses.js` использует `POST /api/addresses/house` с `{magic: ...}`.

## GET `/api/addresses/house/:houseId`

- **Параметр**: `houseId` (number)
- **Успех 200**: `{"house": <houseObject>}`
- **Ошибка 406**: `{"error":"notAcceptable"}`

## PUT `/api/addresses/house/:houseId`

- **Параметр**: `houseId` (number)
- **Body**:
  - `settlementId` (number)
  - `streetId` (number)
  - `houseUuid` (string)
  - `houseType` (string)
  - `houseTypeFull` (string)
  - `houseFull` (string)
  - `house` (string)
  - `companyId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

## POST `/api/addresses/house`

Создаёт дом.

### Обычное создание

- **Body**: те же поля, что и в PUT (кроме `houseId`).
- **Успех 200**: `{"houseId": <number>}`

### “Magic” создание

Если в body есть `magic`, endpoint вызывает `addresses->addHouseByMagic(magic)`.

- **Body**:
  - `magic` (string): идентификатор/uuid, который должен соответствовать Redis-ключу `house_<magic>`
- **Успех 200**: `{"houseId": <number>}`

Ошибки:

- Если backend возвращает `false`: endpoint не задаёт явный код/ошибку, поэтому ответ будет сформирован по дефолтной логике (через `api::ERROR()`).

## DELETE `/api/addresses/house/:houseId`

- **Параметр**: `houseId` (number)
- **Успех 204**: пустое тело
- **Ошибка 406**: `{"error":"notAcceptable"}`

