# `/api/billing/subscriptions` — synchronize billing subscriptions

Implemented in `server/api/billing/subscriptions.php`, exposed through
`POST /frontend/billing/subscriptions`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/billing/subscriptions.php` → class `\api\billing\subscriptions`.
- **Backends**: `billing->syncAutoBlockByContracts()`, `households`, and `customFields` when field values are supplied.
- The endpoint uses `defaultAction = skipMissing`: flats omitted from the batch stay unchanged.

## Request body

`subscribers` is an array of objects. Each item identifies an existing flat by
`subscriberID` (its contract number), or by `buildingUUID` and `flatNumber` together.
An address pair takes precedence; contract-only lookup must identify one flat.

These standard properties retain their dedicated behavior:

- `isActive`: `true`/`1` sets `autoBlock = 0`; `false`/`0` sets `autoBlock = 1`.
- `subscriberID`, `buildingUUID`, `flatNumber`: identify the flat; existing contract synchronization is preserved.
- `agreement`, `addressText`: existing billing custom fields, with automatic creation of their definitions.
- `login`, `password`: update flat credentials.
- `phones`: import subscriber phone links.

### Additional custom fields

Every other property is matched by exact name against `custom_fields.field` for
`apply_to = flat`. Matching values are saved through `modifyValues(..., "patch")`.
The definition must already exist; adding a new field requires configuration only,
without another change to this endpoint. The field's catalog does not restrict matching.

For example, if `branch` is a configured select field with options `1` and `2`:

```json
{
  "subscribers": [
    {"subscriberID": 1234, "isActive": true, "branch": 2}
  ]
}
```

- Omitted fields keep their existing values.
- Unknown properties and definitions for other entities are ignored.
- `null` or an empty string clears an optional additional field.
- Numbers are converted to strings; booleans become `"1"`/`"0"`. Zero is preserved.
- Non-editable select fields accept only configured options. Multiple selects accept
  arrays or JSON array strings and are stored as JSON arrays.
- Objects/arrays are accepted for `editor = json`; other single-value fields require scalars.
- Required values, number/JSON formats and configured regular expressions are validated.
  Button fields do not accept stored values.
- Standard property names listed above are reserved, even if a custom field has the same name.

`isActive` can be omitted when an item supplies a non-empty `phones` list or at least
one configured additional custom field. This leaves `autoBlock` unchanged:

```json
{"subscribers": [{"subscriberID": 1234, "branch": 1}]}
```

If a supplied address pair cannot resolve a flat, an item containing additional custom
fields fails with `customFieldsRequireExactFlatMatch` before modifying the flat. It
cannot silently report success after skipping those fields during contract fallback.

## Responses

- **HTTP 200**: `{"subscriptions": <syncResult>}`. Inspect `updated`, `invalid`, `notFound`,
  `failed` and `errors`; HTTP success does not mean every batch item was applied.
- An invalid custom value rejects its item before any flat changes. Other valid items
  in the batch can still be processed. For example:

```json
{"index": 0, "error": "invalidCustomFieldValue", "field": "branch", "reason": "invalidOption"}
```

- If the custom-field backend is unavailable, the item fails with `cantLoadCustomFieldConfiguration`.
- **HTTP 400**: `{"error":"unknown"}` when the backend returns false (`ANSWER(false)`).
- If the billing backend is missing, the handler returns a plain string `"error"`.

## Regression test

With PHP 8.1+, `pdo_pgsql`, `mbstring`, the server's HTMLPurifier dependency installed,
and a PostgreSQL test connection that can create temporary tables:

```sh
RBT_TEST_PG_DSN='pgsql:host=127.0.0.1;dbname=rbt_test' \
RBT_TEST_PG_USER=rbt_test php tests/billing_subscriptions_custom_fields.php
```

The test uses the real endpoint, billing normalization and custom-field storage with
temporary PostgreSQL tables inside a rolled-back transaction. Household operations are
stubbed, and application tables are not modified. Supply `RBT_TEST_PG_PASSWORD` in the
environment if needed. `RBT_HTMLPURIFIER_AUTOLOAD` can point to an existing
`HTMLPurifier.auto.php` when dependencies live outside `server/vendor`.
