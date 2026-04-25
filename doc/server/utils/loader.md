# `server/utils/loader.php`

This file contains dynamic loaders used by the server, including the **backend loader**.

## `loadBackend($backend, $login = false)`

`loadBackend()` loads a backend implementation based on configuration and returns a **cached instance**.

### Key behavior: returns an existing instance

The function acts as a **service locator**:

- it keeps a singleton-like cache in a global `$backends` array
- repeated calls return the **same backend object** for the same `$backend` name
- if `$login` is provided and the backend was already loaded, the function **switches credentials** on the same instance via `setLogin($login)`

This means `loadBackend()` does **not** create a new object on every call.

### How the backend class is selected

Backend selection is driven by server config:

- `config["backends"][$backend]["backend"]` chooses a **variant** (implementation name).

Expected file structure:

- module file: `server/backends/<backend>/<backend>.php`
- variant file: `server/backends/<backend>/<variant>/<variant>.php`

Expected class name:

- `backends\<backend>\<variant>`

### Custom variant (project customization)

To implement or extend logic for a specific backend without modifying core variants, you can provide a **custom variant**.

- **Config**: set `config["backends"][<backend>]["backend"] = "custom"`.
- **File**: implement `server/backends/<backend>/custom/custom.php`.
- **Class**: `backends\<backend>\custom`

The custom class may extend the module-level base class `backends\<backend>\<backend>` (from `server/backends/<backend>/<backend>.php`) when it exists, and implement the required contract for that backend.

### Error handling

- If the backend is not configured in `config["backends"]`, the function returns `false`.
- If required files are missing or an exception occurs during load/construct:
  - an error is logged
  - `setLastError(i18n("cantLoadBackend", $backend))` is called
  - `false` is returned

## Other loaders in this file

- `loadExtension($extension, $login = false)` — loads an extension from `server/extensions/**`.
- `loadDevice(...)` — loads hardware device classes based on `server/hw/**` model JSON (prefers `custom` class when present).
- `loadConfiguration()` — loads server configuration from `server/config/config.json`.

