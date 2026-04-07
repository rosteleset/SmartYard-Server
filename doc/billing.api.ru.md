# Billing API

Документация по методам интеграции с биллингом, доступным снаружи как:

- `POST /frontend/billing/addresses`
- `POST /frontend/billing/subscriptions`

Внутренние PHP-обработчики лежат в:

- `server/api/billing/addresses.php`
- `server/api/billing/subscriptions.php`

## Предварительная настройка сервера

Чтобы методы `/frontend/billing/addresses` и `/frontend/billing/subscriptions` были доступны на
конкретном инстансе RBT, в серверном конфиге должен быть явно включён billing backend.

Минимально требуется секция в `server/config/config.json`:

```json
"backends": {
  "billing": {
    "backend": "internal"
  }
}
```

Важно:

- оба billing endpoint'а внутри вызывают `loadBackend("billing")`;
- если `backends.billing` не описан в `server/config/config.json`, интеграция не будет работать;
- на практике это может проявляться как отсутствие группы `billing` в
  `/frontend/authorization/methods` и/или ошибка вида `{"error":"methodNotFound"}` при вызове
  `/frontend/billing/*`;
- это именно серверная предпосылка, отдельная от клиентского `/config/config.json`.

Если во внедрении дополнительно используется контур `providers`, он настраивается отдельно:

- на сервере — через `backends.providers`;
- в клиентском runtime config — через модуль `providers`, если нужен UI.

## Общее

- Все методы требуют `Authorization: Bearer <TOKEN>`.
- Формат тела запроса: `Content-Type: application/json`.
- Успешный ответ приходит в стандартной обёртке frontend API:
  - `{"addresses": {...}}` для `/frontend/billing/addresses`
  - `{"subscriptions": {...}}` для `/frontend/billing/subscriptions`
- Ошибка приходит в виде `{"error":"..."}` с HTTP-кодом frontend API.

Примеры ниже используют реальные адресные значения из `https://rbt.sesameware.com` по состоянию
на `2026-03-29`:

- регион: `Тамбовская`, `regionUuid = a9a71961-9363-44ba-91b5-ddf0463aebc2`
- город: `Тамбов`, `cityUuid = ea2a1270-1e19-4224-b1a0-4228b9de3c7a`
- улица: `Интернациональная`, `streetUuid = 47d50419-7bcc-486b-963f-962225731065`
- дом: `69`, `houseUuid = b8ce5933-da5a-4ecd-b389-f081abd8e521`
- существующие квартиры в этом доме: `1..8`

Если запускать примеры импорта адресов на этой же базе, адресные сущности и часть квартир уже
существуют, поэтому в ответе нормально увидеть `skipped`, а не `created`.

В примерах `subscriptions` реальными взяты адресные UUID и номера квартир. Поля
`subscriberID`, `agreement`, `login` и `password` оставлены демонстрационными, потому что это уже
данные биллинга, а не адресного справочника RBT.

`buildingUUID` в методе `subscriptions` должен совпадать с UUID дома, который был загружен через
`/frontend/billing/addresses` в поле `houseUuid`.

## `POST /frontend/billing/addresses`

Метод импортирует адресную иерархию из биллинга в RBT:

- регион;
- район;
- город;
- населённый пункт;
- улицу;
- дом;
- квартиры;
- список доступных услуг дома.

Если адресная сущность уже существует, она не создаётся повторно. Это относится к:

- региону
- району
- городу
- населённому пункту
- улице
- дому

Квартиры тоже не создаются повторно, но для них действует отдельная логика:

- квартира не участвует в сопоставлении по UUID или имени;
- она проверяется внутри конкретного дома по `flatNumber`;
- если квартира с таким номером в доме уже есть, она будет пропущена.

Поиск для этих сущностей идёт сначала по UUID, а если UUID не найден, то по имени. Для fallback
по имени используются именно поля:

- `region`
- `area`
- `city`
- `settlement`
- `street`
- `house`

Поля `*WithType`, `*Type`, `*TypeFull` и `houseFull` в fallback-сопоставлении не участвуют.

Если объект был найден по имени, а UUID отличается, импорт не падает, но в ответе увеличивается
`uuidMismatches`, а в `errors` добавляется запись вида `*UuidMismatch`.

### Формат запроса

Тело запроса:

```json
{
  "addresses": [
    {
      "regionUuid": "a9a71961-9363-44ba-91b5-ddf0463aebc2",
      "region": "Тамбовская",
      "cityUuid": "ea2a1270-1e19-4224-b1a0-4228b9de3c7a",
      "city": "Тамбов",
      "streetUuid": "47d50419-7bcc-486b-963f-962225731065",
      "street": "Интернациональная",
      "houseUuid": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "house": "69"
    }
  ]
}
```

### Поля `addresses[]`

| Поле | Тип | Обязательно | Описание |
| --- | --- | --- | --- |
| `regionUuid` | `string` | да | UUID региона |
| `region` | `string` | да | Название региона |
| `areaUuid` | `string` | нет | UUID района |
| `area` | `string` | нет | Название района |
| `cityUuid` | `string` | нет | UUID города |
| `city` | `string` | нет | Название города |
| `settlementUuid` | `string` | нет | UUID населённого пункта |
| `settlement` | `string` | нет | Название населённого пункта |
| `streetUuid` | `string` | нет | UUID улицы |
| `street` | `string` | нет | Название улицы |
| `houseUuid` | `string` | да | UUID дома |
| `house` | `string` | да | Номер или имя дома |
| `services` | `string[]` | нет | Список услуг дома |
| `flats` | `object[]` | нет | Явный список квартир |
| `flatRanges` | `object[]` | нет | Диапазоны квартир |

### Дополнительные поля адреса

Можно передавать дополнительные атрибуты для нормального заполнения адресных сущностей:

- `regionIsoCode`
- `regionWithType`, `regionType`, `regionTypeFull`
- `areaWithType`, `areaType`, `areaTypeFull`
- `cityWithType`, `cityType`, `cityTypeFull`
- `settlementWithType`, `settlementType`, `settlementTypeFull`
- `streetWithType`, `streetType`, `streetTypeFull`
- `houseType`, `houseTypeFull`, `houseFull`
- `timezone`
- `companyId` - ID управляющей компании, обслуживающей дом

Если часть этих полей не передана, backend подставляет безопасные значения по умолчанию:

- `timezone` -> `"-"` как внутренний маркер "timezone не задан"; при сохранении в БД это
  превращается в `null`
- `houseFull` -> значение `house`
- `companyId` -> `0` (дом без привязки к управляющей компании)
- `*WithType` -> обычное имя сущности

Поля `*WithType`, `*Type`, `*TypeFull` и `houseFull` нужны для сохранения и отображения полного
адреса в RBT. Для fallback-сопоставления они не используются.

### Правила валидации

- Обязательны `regionUuid`, `region`, `houseUuid`, `house`.
- Должен быть указан хотя бы один из вариантов:
  - `areaUuid + area`
  - `cityUuid + city`
- Должен быть указан хотя бы один из вариантов:
  - `settlementUuid + settlement`
  - `streetUuid + street`
- Если передана улица, то должен быть ещё и город или населённый пункт.
- `companyId` должен быть целым числом и означать ID управляющей компании в RBT.
- `services` должен быть массивом строк.
- Разрешённые значения `services`:
  - `internet`
  - `iptv`
  - `ctv`
  - `phone`
  - `cctv`
  - `domophone`
  - `gsm`

### Квартиры: `flats`

`flats` используется, когда биллинг передаёт явный список квартир.

Формат элемента:

| Поле | Тип | Обязательно | Описание |
| --- | --- | --- | --- |
| `flatNumber` | `string` | да | Номер квартиры |
| `floor` | `int` | нет | Этаж, по умолчанию `0` |

Особенности:

- если квартира уже существует в доме, она будет пропущена;
- если `floor` невалиден, backend сохранит `0`.

### Квартиры: `flatRanges`

`flatRanges` используется, когда в биллинге квартиры идут диапазонами.

Формат элемента:

| Поле | Тип | Обязательно | Описание |
| --- | --- | --- | --- |
| `fromFlat` | `int|string` | да | Начало диапазона, только положительное целое |
| `toFlat` | `int|string` | да | Конец диапазона, только положительное целое |
| `floor` | `int` | нет | Этаж для всех квартир диапазона, по умолчанию `0` |

Особенности:

- диапазон разворачивается в последовательность квартир;
- если `fromFlat > toFlat`, диапазон считается невалидным;
- разрешены только положительные целые номера.

### Что делает метод дополнительно

- Если передан `services`, список услуг дома сохраняется в custom field дома `services`.
- Если передан `companyId`, он используется как привязка дома к управляющей компании.
- Если дом уже существует и в payload пришёл `companyId`, текущий импорт обновит `companyId`
  у найденного дома.
- Если `companyId` в payload не пришёл, существующая привязка дома к управляющей компании не
  меняется.
- Если в payload пришёл `timezone`, текущий импорт обновит `timezone` у уже существующих
  `region`, `area` и `city`.
- Если `timezone` в payload не пришёл, существующие timezone у найденных `region`, `area` и
  `city` не меняются.
- Перед сохранением услуги:
  - приводятся к нижнему регистру;
  - дедуплицируются;
  - сортируются;
  - сохраняются как строка через запятую.

### Успешный ответ

```json
{
  "addresses": {
    "processed": 1,
    "invalid": 0,
    "failed": 0,
    "created": {
      "regions": 0,
      "areas": 0,
      "cities": 1,
      "settlements": 0,
      "streets": 1,
      "houses": 1,
      "flats": 4
    },
    "skipped": {
      "regions": 1,
      "areas": 0,
      "cities": 0,
      "settlements": 0,
      "streets": 0,
      "houses": 0,
      "flats": 0
    },
    "servicesUpdated": 1,
    "uuidMismatches": 0,
    "errors": []
  }
}
```

### Основные поля ответа

| Поле | Описание |
| --- | --- |
| `processed` | сколько элементов `addresses[]` обработано |
| `invalid` | сколько элементов отклонено из-за валидации |
| `failed` | сколько внутренних операций не удалось |
| `created.*` | сколько сущностей реально создано |
| `skipped.*` | сколько сущностей уже существовало |
| `servicesUpdated` | сколько домов получили обновление custom field `services` |
| `uuidMismatches` | сколько сущностей были найдены по имени, но с другим UUID |
| `errors[]` | список ошибок и предупреждений с деталями |

### Пример 1. Минимальный импорт дома

```bash
API='https://<host>/frontend/billing/addresses'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "addresses": [
    {
      "regionUuid": "a9a71961-9363-44ba-91b5-ddf0463aebc2",
      "region": "Тамбовская",
      "cityUuid": "ea2a1270-1e19-4224-b1a0-4228b9de3c7a",
      "city": "Тамбов",
      "streetUuid": "47d50419-7bcc-486b-963f-962225731065",
      "street": "Интернациональная",
      "houseUuid": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "house": "69",
      "houseFull": "Россия, г Тамбов, ул Интернациональная, д 69",
      "services": ["internet", "domophone"]
    }
  ]
}
JSON
```

### Пример 2. Импорт дома с `*WithType`, `*Type`, `*TypeFull`, `houseFull`

```bash
API='https://<host>/frontend/billing/addresses'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "addresses": [
    {
      "regionUuid": "a9a71961-9363-44ba-91b5-ddf0463aebc2",
      "region": "Тамбовская",
      "regionWithType": "Тамбовская обл",
      "regionType": "обл",
      "regionTypeFull": "область",
      "cityUuid": "ea2a1270-1e19-4224-b1a0-4228b9de3c7a",
      "city": "Тамбов",
      "cityWithType": "г Тамбов",
      "cityType": "г",
      "cityTypeFull": "город",
      "streetUuid": "47d50419-7bcc-486b-963f-962225731065",
      "street": "Интернациональная",
      "streetWithType": "ул Интернациональная",
      "streetType": "ул",
      "streetTypeFull": "улица",
      "houseUuid": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "house": "69",
      "houseType": "д",
      "houseTypeFull": "дом",
      "houseFull": "Россия, г Тамбов, ул Интернациональная, д 69",
      "timezone": "Europe/Moscow",
      "services": ["internet", "iptv"],
      "flats": [
        { "flatNumber": "1", "floor": 1 },
        { "flatNumber": "2", "floor": 1 },
        { "flatNumber": "3" },
        { "flatNumber": "4" }
      ]
    }
  ]
}
JSON
```

### Пример 3. Импорт дома с явным списком квартир

```bash
API='https://<host>/frontend/billing/addresses'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "addresses": [
    {
      "regionUuid": "a9a71961-9363-44ba-91b5-ddf0463aebc2",
      "region": "Тамбовская",
      "cityUuid": "ea2a1270-1e19-4224-b1a0-4228b9de3c7a",
      "city": "Тамбов",
      "streetUuid": "47d50419-7bcc-486b-963f-962225731065",
      "street": "Интернациональная",
      "houseUuid": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "house": "69",
      "services": ["internet", "iptv"],
      "flats": [
        { "flatNumber": "1", "floor": 1 },
        { "flatNumber": "2", "floor": 1 },
        { "flatNumber": "3" },
        { "flatNumber": "4" }
      ]
    }
  ]
}
JSON
```

### Пример 4. Импорт дома с диапазонами квартир

```bash
API='https://<host>/frontend/billing/addresses'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "addresses": [
    {
      "regionUuid": "a9a71961-9363-44ba-91b5-ddf0463aebc2",
      "region": "Тамбовская",
      "cityUuid": "ea2a1270-1e19-4224-b1a0-4228b9de3c7a",
      "city": "Тамбов",
      "streetUuid": "47d50419-7bcc-486b-963f-962225731065",
      "street": "Интернациональная",
      "houseUuid": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "house": "69",
      "services": ["domophone", "gsm"],
      "flatRanges": [
        { "fromFlat": 1, "toFlat": 4, "floor": 1 },
        { "fromFlat": 5, "toFlat": 8 }
      ]
    }
  ]
}
JSON
```

## `POST /frontend/billing/subscriptions`

Метод синхронизирует состояние абонентских договоров из биллинга с квартирами в RBT.

Что делает метод:

- выставляет `autoBlock` квартиры по признаку активности договора;
- при необходимости обновляет `flat.contract`;
- может обновить `flat.login` и `flat.password`;
- может сохранить `agreement` и `addressText` в custom fields квартиры;
- может добавить телефоны абонента в квартиру RBT.

Важно для `addressText`:

- в текущей реализации это только справочное поле;
- оно просто сохраняется в custom field квартиры и может использоваться для отладки;
- заполнение адресных классификаторов выполняется через `/frontend/billing/addresses`, а не через
  `/frontend/billing/subscriptions`.

Публичный frontend-метод всегда работает в режиме `skipMissing`:

- договоры, которых нет в текущем запросе, не блокируются и не разблокируются автоматически;
- параметр `defaultAction` снаружи не передаётся.

### Логика поиска квартиры

Каждый элемент `subscribers[]` должен содержать хотя бы один способ поиска:

- `subscriberID`
- или пару `buildingUUID + flatNumber`

Порядок поиска:

1. Если есть `buildingUUID + flatNumber`, сначала ищется квартира по этой паре.
2. Если по паре квартира не найдена и есть `subscriberID`, backend пытается найти квартиру по
   `flat.contract = subscriberID`.
3. Если по `subscriberID` найдено несколько квартир, метод возвращает ошибку
   `multipleFlatsByContractFallback`.

Важно:

- если запрос был только по `subscriberID`, fallback по договору считается штатным сценарием;
- если в запросе были и `buildingUUID + flatNumber`, и `subscriberID`, но квартира нашлась только
  по fallback-договору, backend обновляет `login/password` и `phones`;
- `autoBlock` в этом fallback-сценарии обновляется только если в элементе был передан `isActive`;
- `contract`, `agreement` и `addressText` в этом fallback-сценарии не переписываются.

### Как интерпретируется `isActive`

- `true` или `1` -> `autoBlock = 0`
- `false` или `0` -> `autoBlock = 1`
- если `isActive` не передан и элемент используется как `phone-only`-синхронизация, `autoBlock`
  не меняется

### Формат запроса

```json
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "isActive": true,
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "1"
    }
  ]
}
```

### Поля `subscribers[]`

| Поле | Тип | Обязательно | Описание |
| --- | --- | --- | --- |
| `isActive` | `bool|int` | нет* | Состояние договора |
| `subscriberID` | `int` | нет* | Идентификатор договора |
| `buildingUUID` | `string` | нет* | UUID дома |
| `flatNumber` | `string` | нет* | Номер квартиры |
| `agreement` | `string` | нет | Номер договора для custom field квартиры |
| `addressText` | `string` | нет | Справочный текстовый адрес; сохраняется только в custom field квартиры |
| `login` | `string` | нет | Логин квартиры |
| `password` | `string` | нет | Пароль квартиры |
| `phones` | `object[]` | нет | Телефоны, которые нужно привязать к квартире |

\* Обязательно хотя бы одно:

- `subscriberID`
- или пара `buildingUUID + flatNumber`

\* `isActive` обязателен, если элемент должен менять `autoBlock`. Его можно не передавать только
в режиме обновления телефонов, когда вместе с lookup-полями передаётся непустой `phones[]`.

### Поля `phones[]`

| Поле | Тип | Обязательно | Описание |
| --- | --- | --- | --- |
| `phone` | `string|number` | да | Телефон абонента |
| `type` | `string` | нет | `owner` или `regular`, по умолчанию `regular` |

### Правила валидации и поведения

- Каждый элемент `subscribers[]` должен быть объектом.
- `isActive` обязателен для обычной синхронизации статуса договора.
- Если `isActive` не передан, элемент должен содержать непустой `phones[]`; в этом режиме
  `autoBlock` не меняется.
- `subscriberID`, если передан, должен быть положительным целым.
- Если передан `buildingUUID`, то вместе с ним должен быть и `flatNumber`, и наоборот.
- `agreement` и `addressText`, если переданы, должны быть непустыми строками.
- `addressText` не участвует в поиске квартиры и не обновляет адресный справочник.
- `login` и `password`, если переданы, должны быть строками.
- `phones` должен быть массивом объектов.
- Телефон нормализуется до цифр:
  - `+79991234567` -> `79991234567`
  - `+442079460958` -> `442079460958`
  - `+380501234567` -> `380501234567`
- Допустимая длина телефона после нормализации: от `10` до `15` цифр.
- Backend больше не делает автопреобразование локальных российских форматов.
- Телефон нужно передавать сразу с кодом страны. Для российских номеров используйте `+79...`
  или `79...`.
- Локальные форматы без кода страны не поддерживаются:
  - `9991234567` -> ошибка
  - `89123456781` -> ошибка
- Любой другой номер длиной от `10` до `15` цифр после удаления нецифровых символов сохраняется
  как есть.
- Один и тот же телефон нельзя передать дважды с разными `type` в рамках одного subscriber item.

### Что обновляется в квартире

Если передан `isActive`:

- `autoBlock`

При наличии `subscriberID`:

- если квартира найдена по `buildingUUID + flatNumber`, `contract` обновляется значением
  `subscriberID`;
- если запрос был только по `subscriberID`, backend тоже передаёт `contract = subscriberID` в
  patch квартиры, но обычно это то же самое значение, по которому квартира и была найдена.

При наличии `login` и/или `password`:

- если переданы оба поля, сохраняются оба;
- если передано только одно, второе дочитывается из текущей квартиры и сохраняется вместе с ним.

При наличии `agreement` и/или `addressText`:

- если квартира найдена:
  - по `buildingUUID + flatNumber`
  - или запрос был только по `subscriberID`
- создаются или нормализуются определения billing custom fields для `flat`;
- затем значения сохраняются в custom fields квартиры в режиме `patch`.

Для `addressText` это означает только справочное сохранение:

- backend не использует его для поиска квартиры;
- backend не создаёт и не обновляет по нему регион/город/улицу/дом;
- адресный справочник нужно синхронизировать отдельно через `/frontend/billing/addresses`.

### Как работают `phones`

- Если телефон уже привязан к этой квартире, ничего не меняется.
- Если телефон ещё не привязан:
  - вызывается добавление subscriber в RBT;
  - телефон привязывается к текущей квартире.
- Если `type = owner`, для новой связи с текущей квартирой выставляется роль owner.
- Роли этого же subscriber в других квартирах не перетираются.
- Если элемент передан без `isActive`, но с lookup-полями и `phones[]`, метод работает как
  `phone-only`-синхронизация: телефоны обновляются, а `autoBlock` не меняется.

### Успешный ответ

```json
{
  "subscriptions": {
    "processed": 2,
    "updated": 2,
    "invalid": 0,
    "notFound": 0,
    "failed": 0,
    "defaultAction": "skipMissing",
    "missing": {
      "updated": 0,
      "unchanged": 0,
      "failed": 0
    },
    "errors": []
  }
}
```

### Основные поля ответа

| Поле | Описание |
| --- | --- |
| `processed` | сколько элементов `subscribers[]` обработано |
| `updated` | сколько квартир успешно обновлено |
| `invalid` | сколько элементов отклонено по валидации |
| `notFound` | сколько элементов не удалось сопоставить с квартирой |
| `failed` | сколько внутренних операций завершились ошибкой |
| `defaultAction` | всегда `skipMissing` для frontend-метода |
| `missing.*` | служебный блок backend-режима для отсутствующих договоров |
| `errors[]` | список ошибок и предупреждений с деталями |

### Пример 1. Обновление только телефонов без `isActive`

`isActive` можно не передавать, если элемент используется только для добавления телефонов к уже
найденной квартире.

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "1",
      "phones": [
        {
          "phone": "+79990000011",
          "type": "owner"
        }
      ]
    }
  ]
}
JSON
```

В этом примере:

- квартира ищется по `buildingUUID + flatNumber`;
- телефон будет добавлен к квартире, если его там ещё нет;
- `autoBlock` не изменится, потому что `isActive` не передан.

### Пример 2. Минимальный запрос без `agreement`

`agreement` не является обязательным полем. Минимально достаточно передать способ поиска квартиры
и `isActive`.

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "isActive": true
    }
  ]
}
JSON
```

В этом примере:

- `agreement` не передаётся;
- `addressText`, `login`, `password`, `phones` тоже не передаются;
- backend просто найдёт квартиру по `subscriberID` и обновит `autoBlock`.

### Пример 3. Основной сценарий: поиск по договору и по адресу

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "agreement": "1234",
      "isActive": true,
      "addressText": "Россия, г Тамбов, ул Интернациональная, д 69, кв 1",
      "login": "demo-flat-1",
      "password": "secret-1",
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "1"
    }
  ]
}
JSON
```

Что произойдёт:

- квартира ищется по `buildingUUID + flatNumber`;
- `autoBlock` станет `0`, потому что `isActive=true`;
- `contract` станет `1234`;
- обновятся `login/password`;
- в custom fields квартиры будут записаны `agreement` и `addressText`.

### Пример 4. Поиск только по адресу квартиры

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "2",
      "agreement": "A-0002",
      "isActive": false
    }
  ]
}
JSON
```

Что произойдёт:

- квартира ищется только по `buildingUUID + flatNumber`;
- `autoBlock` станет `1`, потому что `isActive=false`;
- `agreement` будет записан в custom fields;
- `contract` не обновится, потому что `subscriberID` не передан.

### Пример 5. Поиск только по `subscriberID`

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "agreement": "1234",
      "isActive": true
    }
  ]
}
JSON
```

Этот режим работает, только если по `flat.contract = 1234` находится ровно одна квартира.

### Пример 6. Синхронизация телефонов вместе со статусом договора

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "isActive": true,
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "1",
      "phones": [
        {
          "phone": "+79990000011",
          "type": "owner"
        },
        {
          "phone": "+79990000012",
          "type": "regular"
        }
      ]
    }
  ]
}
JSON
```

### Пример 7. Пакетная синхронизация нескольких квартир

```bash
API='https://<host>/frontend/billing/subscriptions'
TOKEN='<TOKEN>'

curl -sS "$API" \
  -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @- <<'JSON'
{
  "subscribers": [
    {
      "subscriberID": 1234,
      "agreement": "1234",
      "isActive": true,
      "addressText": "Россия, г Тамбов, ул Интернациональная, д 69, кв 1",
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "1"
    },
    {
      "subscriberID": 1235,
      "agreement": "1235",
      "isActive": true,
      "addressText": "Россия, г Тамбов, ул Интернациональная, д 69, кв 2",
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "2"
    },
    {
      "buildingUUID": "b8ce5933-da5a-4ecd-b389-f081abd8e521",
      "flatNumber": "3",
      "isActive": false
    }
  ]
}
JSON
```

## Частые причины ошибок

### Для `/frontend/billing/addresses`

- `invalidRequiredFields` — нет обязательных `regionUuid/region/houseUuid/house`
- `missingAreaOrCity` — не переданы ни район, ни город
- `missingSettlementOrStreet` — не переданы ни населённый пункт, ни улица
- `streetRequiresCityOrSettlement` — улица есть, но нет города и населённого пункта
- `invalidServices` / `unknownService` — проблемы со списком услуг
- `invalidFlats` / `invalidFlatRanges` / `invalidFlatRange` — проблемы с описанием квартир

### Для `/frontend/billing/subscriptions`

- `invalidItem` — элемент `subscribers[]` невалиден
- `invalidSubscriberID` — некорректный `subscriberID`
- `buildingUUIDAndFlatRequiredTogether` — передана только одна часть пары
- `invalidBuildingUUIDFlat` — пара передана, но значения пустые/некорректные
- `noLookupParams` — нет ни `subscriberID`, ни пары `buildingUUID + flatNumber`
- `flatNotFound` — квартира не найдена ни по паре, ни по договору
- `multipleFlatsByContractFallback` — по `subscriberID` найдено больше одной квартиры
- `phonesRequiredWithoutIsActive` — `isActive` не передан, но `phones[]` отсутствует
- `invalidPhone` / `invalidPhoneType` / `duplicatePhoneWithDifferentType` — проблемы с телефонами
- `cantModifyFlat` / `cantModifyCustomFields` / `cantAddSubscriberPhone` — внутренняя ошибка сохранения

## Рекомендации по интеграции

- Сначала загрузите адресный справочник через `/frontend/billing/addresses`.
- После этого синхронизируйте договоры через `/frontend/billing/subscriptions`.
- Если используете режим поиска по адресу, следите, чтобы:
  - `buildingUUID` был равен `houseUuid`, который уже импортирован в RBT;
  - `flatNumber` совпадал с номером квартиры в RBT.
- Если хотите обновлять billing custom fields `agreement` и `addressText`, достаточно просто
  начать передавать эти поля в `subscriptions`: backend сам создаст нужные определения для
  `flat`, если их ещё нет.
- `addressText` воспринимайте как справочное/отладочное поле квартиры. Для загрузки и обновления
  адресных классификаторов используйте `/frontend/billing/addresses`.
