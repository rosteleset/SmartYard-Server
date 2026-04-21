# customFields

## What It Is

`customFields` in RBT let you extend the standard field set for different entities without
changing the schema for every new attribute.

In practice, this makes it possible to:

- add new fields for an entity via configuration
- store their values separately from the entity's main table
- control how the field is rendered in the UI
- group fields into tabs
- define requiredness, order, editor type, and select options
- attach an extra button or action to a field via `magic_*`
- use i18n keys in `fieldDisplay`, `fieldDescription`, and `tab`

The mechanism itself is backend-generic, but in the current `client` full rendering and editing
support is implemented for `apply_to = flat` in the `addresses/houses` module.

## Where Data Is Stored

### 1. Field Definitions: `custom_fields`

The `custom_fields` table stores the field definition and almost all presentation settings.

Main columns:

- `apply_to` - which entity the field belongs to, for example `flat`
- `catalog` - a logical group or source of the field, for example `billing`
- `type` - logical field type, for example `text`, `select`, `button`
- `field` - machine field name
- `field_display` - field label or i18n key
- `field_description` - field description or i18n key
- `editor` - form editor type, for example `text`, `area`, `json`, `yesno`, `noyes`
- `format` - extra format flags, for example `multiple`, `editable`
- `required` - whether the field is required
- `regex` - server-defined mask for client-side validation
- `add` - whether the field is shown in the create form
- `modify` - whether the field is shown in the edit form
- `tab` - the tab where the field should be displayed
- `weight` - field order
- `magic_class`, `magic_function`, `magic_hint` - parameters for an extra button/action

There is no separate table just for presentation settings here: most such settings are stored
directly in `custom_fields`.

### 2. Select Options: `custom_fields_options`

If a field has a choice type, its options are stored in `custom_fields_options`.

Main columns:

- `custom_field_id` - reference to the record in `custom_fields`
- `option` - value
- `option_display` - display text
- `display_order` - option order

### 3. Per-Object Values: `custom_fields_values`

Actual field values are stored in `custom_fields_values`.

Main columns:

- `apply_to` - entity
- `id` - ID of the specific object of that entity
- `field` - machine field name
- `value` - value

Important: values are not bound to `custom_field_id`; they are bound to the combination
`apply_to + id + field`.

## How It Works in the Backend

The main backend is located in `server/backends/customFields/internal/internal.php`.

It performs three basic operations:

- reads field values via `getValues($applyTo, $id)`
- saves values via `modifyValues($applyTo, $id, $set, $mode = "replace")`
- reads field configuration via `getFields($applyTo)`

`getFields()`:

- reads rows from `custom_fields`
- sorts them by `weight`
- appends `options` from `custom_fields_options`
- returns ready-to-use UI configuration

`modifyValues()`:

- updates existing values
- inserts new values
- in `replace` mode, removes fields that are not present in `$set`
- in `patch` mode, changes only mentioned fields and leaves the rest untouched
- if an empty value is passed for a mentioned field, removes exactly that field
- returns an error if DB insert/update/delete fails

Periodic `cleanup()`:

- removes values without a valid `apply_to`
- removes values for fields that no longer exist in `custom_fields`
- removes orphaned `custom_fields_options`

## How It Is Used in the Flat API

For flats (`flat`) two API methods are used:

### Field Configuration

`GET /frontend/houses/customFieldsConfiguration`

Returns the configuration of available custom fields, currently in this format:

```json
{
  "customFieldsConfiguration": {
    "flat": [ ... ]
  }
}
```

### Field Values

`GET /frontend/houses/customFields/flat?id=<flatId>`

Returns custom field values for a specific flat.

`PUT /frontend/houses/customFields/flat`

Accepts:

```json
{
  "id": 123,
  "customFields": {
    "agreement": "1235",
    "addressText": "demo flat 2 block"
  }
}
```

And stores the values in `custom_fields_values`.

## What the UI Currently Supports for Flats

In `client/modules/addresses/houses.js`, custom fields for `flat` already:

- are loaded from `houses/customFieldsConfiguration`
- read values from `houses/customFields/flat`
- are saved both when editing an existing flat and when creating a new one
- can be displayed across multiple tabs
- use `tab` from the database
- localize `fieldDisplay`, `fieldDescription`, and `tab` if they contain an i18n key

Supported rendering scenarios:

- standard text field
- multiline field via `editor = area`
- `email`, `tel`
- JSON field via `editor = json`
- yes/no via `editor = yesno` or `editor = noyes`
- `select` and multi-select
- editable `select` if `format` contains `editable`
- standalone action button via `type = button`
- button to the right of a normal field via `magic_function + magic_class`

Order and tabs:

- field order inside one tab is defined by `weight`
- the tab is taken from `custom_fields.tab`
- if `tab` is empty, the shared fallback `customFields` is used

### How to Add a customField to an Existing RBT Tab

The RBT form groups fields into tabs by the exact value of `field.tab`.

For custom fields, this works as follows:

- `custom_fields.tab` is read
- if it contains an i18n key, the client localizes it first
- then the field is placed into the tab with the same final title

This means that to add a customField to an existing tab you should not invent a new tab name.
Instead, write into `custom_fields.tab` the same key or the same text already used by the
standard fields of that form.

Recommended approach: use the i18n key of the standard tab rather than the localized text.
Then the same configuration will work correctly in different UI languages.

Examples for the flat form (`addresses/houses`):

- `tab = 'addresses.primary'` - the field goes to the `Primary` tab
- `tab = 'addresses.cars'` - the field goes to the `Cars` tab
- `tab = 'other'` - the field goes to the `Other` tab, if that form displays it

Important:

- if you write a new `tab` value that does not match any standard tab, the UI will create a new
  separate tab
- if you leave `tab` empty, the field will not go to the standard `Other` tab; it will fall back
  to `customFields`
- therefore, to place the field specifically into `Other`, use `tab = 'other'`, not `null` or an
  empty string

## What `magic_class`, `magic_function`, `magic_hint` Mean

These are UI hooks for non-standard field behavior.

- `magic_function` - the JS function name to call
- `magic_class` - CSS class of the button or icon
- `magic_hint` - tooltip text or button label

There are two main scenarios:

### 1. Button Field

If `type = button`, the field becomes a standalone button.

Such a field:

- does not store a user value
- is rendered only if `magic_function` is specified
- calls `modules.custom[magic_function]`

### 2. Normal Field with a Button on the Right

If a field is not `button`, but `magic_function` and `magic_class` are set, a button appears next
to the input and calls the specified function.

## How to Use customFields

The basic flow is:

### 1. Create a Field Definition

Add a record to `custom_fields`:

- choose `apply_to`, for example `flat`
- set `field`
- set the field type (`type`, `editor`, `format`)
- set user-facing text or i18n keys in `field_display`, `field_description`, `tab`
- set `add`, `modify`, `required`, `weight`

Example for a simple text field:

```sql
insert into custom_fields (
    apply_to,
    catalog,
    type,
    field,
    field_display,
    field_description,
    editor,
    add,
    modify,
    tab,
    weight
) values (
    'flat',
    'custom',
    'text',
    'passportNumber',
    'Passport number',
    'Additional flat attribute',
    'text',
    1,
    1,
    'Documents',
    100
);
```

### 2. Add Select Options If Needed

For `select` fields, populate `custom_fields_options`.

### 3. Open the Flat Form in the UI

If the field is configured for `flat` and allowed by `add` or `modify`, it will appear:

- in the flat creation form
- in the flat edit form

### 4. Save a Value

The UI will send values to `PUT /frontend/houses/customFields/flat`, and the backend will store
them in `custom_fields_values`.

## Billing as an Example

`billing/subscriptions` can now use `customFields` for flats.

At the moment this mechanism automatically supports:

- `agreement`
- `addressText`

During billing sync:

- the backend can create missing field definitions in `custom_fields`
- it normalizes their settings if these are billing fields
- it writes values to `custom_fields_values`

These fields use the following i18n keys:

- `addresses.customFields.billing.tab`
- `addresses.customFields.billing.agreement.fieldDisplay`
- `addresses.customFields.billing.agreement.fieldDescription`
- `addresses.customFields.billing.addressText.fieldDisplay`
- `addresses.customFields.billing.addressText.fieldDescription`

## How Translations Work for Billing customFields

The client can localize `fieldDisplay`, `fieldDescription`, and `tab` if they contain keys.

For billing fields, the actual translations are currently placed into client custom i18n:

- `client/modules/addresses/custom/i18n/ru.json`
- `client/modules/addresses/custom/i18n/en.json`

Those keys live in the `addresses` module namespace, for example:

- `customFields.billing.tab`
- `customFields.billing.agreement.fieldDisplay`

For the browser to load these translations, the frontend runtime config must include:

```json
"customSubModules": {
  "addresses": []
}
```

This refers specifically to the client config loaded by the browser as `/config/config.json`.

## Limitations and Practical Notes

- the backend mechanism is generic, but client support is currently implemented specifically for
  `flat`
- if you remove a field definition from `custom_fields`, `cleanup()` will later remove the orphaned
  values too
- if `fieldDisplay` or `tab` contains an i18n key but the required translation is not loaded, the
  UI will show the key itself
- if `type = button` is used, this is an action element rather than a value storage field
- `weight` affects not only field order, but also the effective order of custom tabs in the form,
  because tabs are built from the first matching field encountered
