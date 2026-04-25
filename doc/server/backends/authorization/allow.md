# Variant: `allow` (`server/backends/authorization/allow/allow.php`)

## Purpose

Allow-all authorization backend.

This variant:

- always returns `true` from `allow($params)`
- exposes all indexed methods as allowed methods
- does **not** implement rights management (read-only)

## Behavior

- `allow($params)` → `true`
- `allowedMethods($uid)` → `methods()` (returns all indexed methods)
- `getRights()` / `setRights(...)` → `false` (stubs)
- `capabilities()` → `false` (no `rw` mode)

## Notes

Because it has no `capabilities()["mode"] === "rw"`, the `api/authorization/rights` endpoint is disabled by indexing logic.

