# Billing API

Documentation for billing integration methods exposed externally as:

- `POST /frontend/billing/addresses`
- `POST /frontend/billing/subscriptions`

Internal PHP handlers are located at:

- `server/api/billing/addresses.php`
- `server/api/billing/subscriptions.php`

## Server-side prerequisite

For `/frontend/billing/addresses` and `/frontend/billing/subscriptions` to be available on a
specific RBT instance, `server/config/config.json` must include the billing backend:

```json
"backends": {
  "billing": {
    "backend": "internal"
  }
}
```

Without this section, the billing API will not be available on the instance.

## General

- All methods require `Authorization: Bearer <TOKEN>`.
- Request body format: `Content-Type: application/json`.
- A successful response is returned in the standard frontend API wrapper:
  - `{"addresses": {...}}` for `/frontend/billing/addresses`
  - `{"subscriptions": {...}}` for `/frontend/billing/subscriptions`
- Errors are returned as `{"error":"..."}` with the HTTP status code used by the frontend API.

The examples below use real address values from `https://rbt.sesameware.com` as of
`2026-03-29`:

- region: `Тамбовская`, `regionUuid = a9a71961-9363-44ba-91b5-ddf0463aebc2`
- city: `Тамбов`, `cityUuid = ea2a1270-1e19-4224-b1a0-4228b9de3c7a`
- street: `Интернациональная`, `streetUuid = 47d50419-7bcc-486b-963f-962225731065`
- house: `69`, `houseUuid = b8ce5933-da5a-4ecd-b389-f081abd8e521`
- existing flats in this house: `1..8`

If you run the address import examples against the same database, address entities and some
flats already exist there, so seeing `skipped` instead of `created` is expected.

In the `subscriptions` examples, the address UUIDs and flat numbers are real. The
`subscriberID`, `agreement`, `login`, and `password` fields remain illustrative because those
belong to billing data, not to the RBT address directory.

In the `subscriptions` method, `buildingUUID` must match the house UUID previously loaded via
`/frontend/billing/addresses` in the `houseUuid` field.

## `POST /frontend/billing/addresses`

This method imports the address hierarchy from billing into RBT:

- region
- area
- city
- settlement
- street
- house
- flats
- the list of house services

If an address entity already exists, it is not created again. This applies to:

- region
- area
- city
- settlement
- street
- house

Flats are not created again either, but they use separate matching logic:

- a flat does not participate in UUID or name matching
- it is checked inside a specific house by `flatNumber`
- if a flat with the same number already exists in that house, it is skipped

For these entities, lookup is performed by UUID first, and if the UUID is not found, by name.
The fallback name matching uses exactly these fields:

- `region`
- `area`
- `city`
- `settlement`
- `street`
- `house`

The `*WithType`, `*Type`, `*TypeFull`, and `houseFull` fields are not used in fallback matching.

If an object was found by name but the UUID differs, the import does not fail, but the response
increments `uuidMismatches`, and `errors` receives an entry of the `*UuidMismatch` form.

### Request Format

Request body:

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

### `addresses[]` Fields

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `regionUuid` | `string` | yes | Region UUID |
| `region` | `string` | yes | Region name |
| `areaUuid` | `string` | no | Area UUID |
| `area` | `string` | no | Area name |
| `cityUuid` | `string` | no | City UUID |
| `city` | `string` | no | City name |
| `settlementUuid` | `string` | no | Settlement UUID |
| `settlement` | `string` | no | Settlement name |
| `streetUuid` | `string` | no | Street UUID |
| `street` | `string` | no | Street name |
| `houseUuid` | `string` | yes | House UUID |
| `house` | `string` | yes | House number or name |
| `services` | `string[]` | no | List of house services |
| `flats` | `object[]` | no | Explicit flat list |
| `flatRanges` | `object[]` | no | Flat ranges |

### Additional Address Fields

You can pass additional attributes so address entities are stored more completely:

- `regionIsoCode`
- `regionWithType`, `regionType`, `regionTypeFull`
- `areaWithType`, `areaType`, `areaTypeFull`
- `cityWithType`, `cityType`, `cityTypeFull`
- `settlementWithType`, `settlementType`, `settlementTypeFull`
- `streetWithType`, `streetType`, `streetTypeFull`
- `houseType`, `houseTypeFull`, `houseFull`
- `timezone`
- `companyId` - the ID of the management company servicing the house

If some of these fields are not provided, the backend applies safe defaults:

- `timezone` -> `"-"` as an internal marker meaning "timezone is not set"; when stored in the
  database this becomes `null`
- `houseFull` -> the value of `house`
- `companyId` -> `0` (house without a management company binding)
- `*WithType` -> the plain entity name

The `*WithType`, `*Type`, `*TypeFull`, and `houseFull` fields are used for storing and displaying
the full address in RBT. They are not used for fallback matching.

### Validation Rules

- `regionUuid`, `region`, `houseUuid`, and `house` are required.
- At least one of the following must be provided:
  - `areaUuid + area`
  - `cityUuid + city`
- At least one of the following must be provided:
  - `settlementUuid + settlement`
  - `streetUuid + street`
- If `street` is provided, either `city` or `settlement` must also be provided.
- `companyId` must be an integer and must represent an RBT management company ID.
- `services` must be an array of strings.
- Allowed `services` values:
  - `internet`
  - `iptv`
  - `ctv`
  - `phone`
  - `cctv`
  - `domophone`
  - `gsm`

### Flats: `flats`

`flats` is used when billing sends an explicit flat list.

Item format:

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `flatNumber` | `string` | yes | Flat number |
| `floor` | `int` | no | Floor, defaults to `0` |

Behavior:

- if the flat already exists in the house, it is skipped
- if `floor` is invalid, the backend stores `0`

### Flats: `flatRanges`

`flatRanges` is used when billing provides flats as ranges.

Item format:

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `fromFlat` | `int|string` | yes | Range start, positive integer only |
| `toFlat` | `int|string` | yes | Range end, positive integer only |
| `floor` | `int` | no | Floor for all flats in the range, defaults to `0` |

Behavior:

- the range expands into a flat sequence
- if `fromFlat > toFlat`, the range is invalid
- only positive integer flat numbers are allowed

### Additional Behavior

- If `services` is provided, the house service list is stored in the house custom field
  `services`.
- If `companyId` is provided, it is used as the house-to-management-company binding.
- If the house already exists and `companyId` is present in the payload, the current import
  updates `companyId` for the matched house.
- If `companyId` is absent in the payload, the existing management company binding is left
  unchanged.
- If `timezone` is present in the payload, the current import updates `timezone` for existing
  `region`, `area`, and `city`.
- If `timezone` is absent in the payload, existing timezones of matched `region`, `area`, and
  `city` are left unchanged.
- Before storing services, they are:
  - lowercased
  - deduplicated
  - sorted
  - stored as a comma-separated string

### Successful Response

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

### Main Response Fields

| Field | Description |
| --- | --- |
| `processed` | how many `addresses[]` items were processed |
| `invalid` | how many items were rejected by validation |
| `failed` | how many internal operations failed |
| `created.*` | how many entities were actually created |
| `skipped.*` | how many entities already existed |
| `servicesUpdated` | how many houses received a `services` custom field update |
| `uuidMismatches` | how many entities were found by name but with a different UUID |
| `errors[]` | list of errors and warnings with details |

### Example 1. Minimal House Import

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

### Example 2. House Import with `*WithType`, `*Type`, `*TypeFull`, `houseFull`

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

### Example 3. House Import with an Explicit Flat List

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

### Example 4. House Import with Flat Ranges

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

This method synchronizes subscriber contract state from billing with flats in RBT.

What the method does:

- sets `autoBlock` on the flat according to the contract activity flag
- updates `flat.contract` when needed
- can update `flat.login` and `flat.password`
- can save `agreement` and `addressText` into flat custom fields
- can add subscriber phone numbers to the flat in RBT

Important note about `addressText`:

- in the current implementation it is a reference-only field
- it is simply stored in the flat custom field and can be useful for debugging
- address classifier loading is done via `/frontend/billing/addresses`, not via
  `/frontend/billing/subscriptions`

The public frontend method always runs in `skipMissing` mode:

- contracts absent from the current request are not automatically blocked or unblocked
- the `defaultAction` parameter is not exposed externally

### Flat Lookup Logic

Each `subscribers[]` item must contain at least one flat lookup option:

- `subscriberID`
- or the `buildingUUID + flatNumber` pair

Lookup order:

1. If `buildingUUID + flatNumber` is present, the flat is first searched by that pair.
2. If the flat is not found by that pair and `subscriberID` is present, the backend tries to
   find the flat by `flat.contract = subscriberID`.
3. If `subscriberID` matches multiple flats, the method returns
   `multipleFlatsByContractFallback`.

Important:

- if the request only contains `subscriberID`, fallback by contract is the normal scenario
- if both `buildingUUID + flatNumber` and `subscriberID` were sent, but the flat was found only
  via contract fallback, the backend updates `login/password` and `phones`
- in that fallback scenario, `autoBlock` is updated only if `isActive` was present in the item
- in that fallback scenario, `contract`, `agreement`, and `addressText` are not overwritten

### How `isActive` Is Interpreted

- `true` or `1` -> `autoBlock = 0`
- `false` or `0` -> `autoBlock = 1`
- if `isActive` is omitted and the item is used for `phone-only` synchronization, `autoBlock`
  remains unchanged

### Request Format

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

### `subscribers[]` Fields

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `isActive` | `bool|int` | no* | Contract state |
| `subscriberID` | `int` | no* | Contract identifier |
| `buildingUUID` | `string` | no* | House UUID |
| `flatNumber` | `string` | no* | Flat number |
| `agreement` | `string` | no | Agreement number for the flat custom field |
| `addressText` | `string` | no | Reference address text; stored only in the flat custom field |
| `login` | `string` | no | Flat login |
| `password` | `string` | no | Flat password |
| `phones` | `object[]` | no | Phones to attach to the flat |

\* At least one of the following is required:

- `subscriberID`
- or the `buildingUUID + flatNumber` pair

\* `isActive` is required if the item must change `autoBlock`. It may be omitted only in
phone-only mode, when a non-empty `phones[]` is provided together with lookup fields.

### `phones[]` Fields

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `phone` | `string|number` | yes | Subscriber phone number |
| `type` | `string` | no | `owner` or `regular`, defaults to `regular` |

### Validation Rules and Behavior

- Each `subscribers[]` item must be an object.
- `isActive` is required for normal contract-state synchronization.
- If `isActive` is omitted, the item must contain a non-empty `phones[]`; in that mode,
  `autoBlock` is not changed.
- `subscriberID`, if provided, must be a positive integer.
- If `buildingUUID` is provided, `flatNumber` must be provided too, and vice versa.
- `agreement` and `addressText`, if provided, must be non-empty strings.
- `addressText` is not used in flat lookup and does not update the address directory.
- `login` and `password`, if provided, must be strings.
- `phones` must be an array of objects.
- Phone numbers are normalized to digits only:
  - `+79991234567` -> `79991234567`
  - `+442079460958` -> `442079460958`
  - `+380501234567` -> `380501234567`
- Allowed phone length after normalization: from `10` to `15` digits.
- The backend no longer auto-converts local Russian phone formats.
- Phones must be sent with the country code already included. For Russian numbers, use `+79...`
  or `79...`.
- Local formats without a country code are not supported:
  - `9991234567` -> error
  - `89123456781` -> error
- Any other number with length `10..15` after removing non-digit characters is stored as-is.
- The same phone cannot be sent twice with different `type` values within one subscriber item.

### What Gets Updated in the Flat

If `isActive` is provided:

- `autoBlock`

If `subscriberID` is provided:

- if the flat was found by `buildingUUID + flatNumber`, `contract` is updated to `subscriberID`
- if the request only used `subscriberID`, the backend also passes `contract = subscriberID` in
  the flat patch, but in practice this is usually the same value the flat was found by

If `login` and/or `password` are provided:

- if both fields are sent, both are stored
- if only one is sent, the other is read from the current flat and saved together with it

If `agreement` and/or `addressText` are provided:

- if the flat is found:
  - by `buildingUUID + flatNumber`
  - or if the request used only `subscriberID`
- billing custom field definitions for `flat` are created or normalized
- then the values are saved into the flat custom fields in `patch` mode

For `addressText`, this means reference-only storage:

- the backend does not use it to find the flat
- the backend does not create or update region/city/street/house from it
- the address directory must be synchronized separately via `/frontend/billing/addresses`

### How `phones` Work

- If the phone is already linked to this flat, nothing changes.
- If the phone is not yet linked:
  - subscriber creation/linking in RBT is called
  - the phone is linked to the current flat
- If `type = owner`, the new link for the current flat gets the owner role.
- Roles of the same subscriber in other flats are not overwritten.
- If an item is sent without `isActive`, but with lookup fields and `phones[]`, the method works
  as `phone-only` synchronization: phones are updated, while `autoBlock` remains unchanged.

### Successful Response

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

### Main Response Fields

| Field | Description |
| --- | --- |
| `processed` | how many `subscribers[]` items were processed |
| `updated` | how many flats were updated successfully |
| `invalid` | how many items were rejected by validation |
| `notFound` | how many items could not be matched to a flat |
| `failed` | how many internal operations failed |
| `defaultAction` | always `skipMissing` for the frontend method |
| `missing.*` | service block from the backend mode for missing contracts |
| `errors[]` | list of errors and warnings with details |

### Example 1. Update Phones Only Without `isActive`

`isActive` may be omitted if the item is used only to add phone numbers to an already known flat.

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

In this example:

- the flat is searched by `buildingUUID + flatNumber`
- the phone will be added to the flat if it is not already linked there
- `autoBlock` will not change because `isActive` is not present

### Example 2. Minimal Request Without `agreement`

`agreement` is not a required field. The minimum is a way to find the flat plus `isActive`.

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

In this example:

- `agreement` is not sent
- `addressText`, `login`, `password`, and `phones` are not sent either
- the backend simply finds the flat by `subscriberID` and updates `autoBlock`

### Example 3. Main Scenario: Lookup by Contract and Address

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

What happens:

- the flat is searched by `buildingUUID + flatNumber`
- `autoBlock` becomes `0` because `isActive=true`
- `contract` becomes `1234`
- `login/password` are updated
- `agreement` and `addressText` are written to the flat custom fields

### Example 4. Lookup by Flat Address Only

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

What happens:

- the flat is searched only by `buildingUUID + flatNumber`
- `autoBlock` becomes `1` because `isActive=false`
- `agreement` is written to the custom fields
- `contract` is not updated because `subscriberID` is not present

### Example 5. Lookup by `subscriberID` Only

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

This mode only works if `flat.contract = 1234` matches exactly one flat.

### Example 6. Synchronize Phones Together with Contract State

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

### Example 7. Batch Synchronization of Multiple Flats

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

## Common Error Causes

### For `/frontend/billing/addresses`

- `invalidRequiredFields` - missing required `regionUuid/region/houseUuid/house`
- `missingAreaOrCity` - neither area nor city was provided
- `missingSettlementOrStreet` - neither settlement nor street was provided
- `streetRequiresCityOrSettlement` - street is present, but city and settlement are both missing
- `invalidServices` / `unknownService` - issues with the services list
- `invalidFlats` / `invalidFlatRanges` / `invalidFlatRange` - issues in the flat description

### For `/frontend/billing/subscriptions`

- `invalidItem` - invalid `subscribers[]` item
- `invalidSubscriberID` - invalid `subscriberID`
- `buildingUUIDAndFlatRequiredTogether` - only one part of the pair was provided
- `invalidBuildingUUIDFlat` - the pair was provided, but the values are empty/invalid
- `noLookupParams` - neither `subscriberID` nor `buildingUUID + flatNumber` was provided
- `flatNotFound` - the flat was not found by pair or by contract
- `multipleFlatsByContractFallback` - `subscriberID` matched more than one flat
- `phonesRequiredWithoutIsActive` - `isActive` is not provided and `phones[]` is missing
- `invalidPhone` / `invalidPhoneType` / `duplicatePhoneWithDifferentType` - phone-related issues
- `cantModifyFlat` / `cantModifyCustomFields` / `cantAddSubscriberPhone` - internal save error

## Integration Recommendations

- First load the address directory via `/frontend/billing/addresses`.
- Then synchronize contracts via `/frontend/billing/subscriptions`.
- If you use address-based lookup, make sure:
  - `buildingUUID` equals the `houseUuid` already imported into RBT
  - `flatNumber` matches the flat number in RBT
- If you want to update the billing custom fields `agreement` and `addressText`, just start
  sending these fields in `subscriptions`: the backend will create the required `flat`
  definitions automatically if they do not exist yet.
- Treat `addressText` as a reference/debug field of the flat. Use
  `/frontend/billing/addresses` to load and update address classifiers.
