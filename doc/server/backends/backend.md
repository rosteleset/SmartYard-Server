# `server/backends/backend.php` — base backend class

This file defines the base class for all backends: `backends\backend`.

Backends are pluggable domain/service implementations configured under `server/config/config.json` in the `"backends"` section.

## Namespace and class

- **Namespace**: `backends`
- **Base class**: `backends\backend` (abstract)

## Constructor and core fields

### `__construct($config, $db, $redis, $login = false)`

The base constructor wires shared dependencies and derives backend identity:

- **`$config`**: full server config (decoded JSON/JSON5).
- **`$db`**: default PDO database wrapper/connection.
- **`$redis`**: Redis connection.
- **`$login`**: optional login override; otherwise derived from global `$params["_login"]` or `"-"`.

It also derives:

- **`$uid`**:
  - `-1` for anonymous (`"-"`)
  - `0` for `"admin"`
  - otherwise resolved via `loadBackend("users")->getUidByLogin($login)`
- **`$backend`** and **`$variant`** from the concrete class name (via `get_class($this)` and splitting by `\`).
- **`$bconfig`** as a shortcut to `config["backends"][$backend]`.

### Common fields

The base class stores:

- `$config`, `$bconfig`
- `$db`, `$redis`
- `$login`, `$uid`
- `$backend`, `$variant`
- `$cache` (in-memory per-instance cache used by `cacheGet/cacheSet/unCache/clearCache`)

## Optional capability hooks

Backends can implement optional behaviors. Defaults are conservative “no-op” returns:

- `capabilities()` → `false`
- `cleanup()` → `false` (garbage collection)
- `allow($params)` → `false` (access rights regulator)
- `usage($object, $id)` → `false` (object usage check)
- `cron($part)` → `true` (scheduled work for `minutely/5min/hourly/daily/weekly/monthly`)
- `check()` → `true` (self-check/health check)

## Credentials helpers

- `setCreds($uid, $login)` sets the current credentials.
- `setLogin($login)` changes login and resolves a new uid via the `users` backend if needed.

## Backend cache helpers (Redis + in-memory)

Caching is **per-backend + per-user**:

Key format:

- `CACHE:{BACKEND}:{key}:{uid}`

### `cacheGet($key)`

- Returns decoded JSON value from in-memory cache first.
- If not found and `uid > 0`, loads from Redis and caches it in memory.
- Returns `false` on miss.

### `cacheSet($key, $value, $memOnly = false)`

- Stores JSON-encoded `$value` in in-memory cache.
- If `uid > 0` and not `$memOnly`, also stores in Redis with TTL:
  - `config["redis"]["backends_cache_ttl"]`, or default 3 days.

### `unCache($key)`

- Removes from in-memory cache and from Redis (only if `uid > 0`).

### `clearCache()`

- Clears in-memory cache and deletes all Redis keys matching `CACHE:{BACKEND}:*`.
- Returns number of deleted Redis keys.

## CLI hooks

Backends can provide CLI behavior:

- `cli($args)` → default `false`
- `cliUsage()` → default `[]` (used to build global CLI help)

How registration, `php cli.php <backend> …`, and `init`/`pre`/`run` stages work: [cli.php](../entrypoints/cli.md). **Per-backend flags** in this repo: [Backend CLI extensions](./cli-extensions.md).

## Related code

- Backend loader and dispatch: `server/utils/loader.php` and `loadBackend(...)`
- Configuration: `server/config/config.json` → `"backends": { ... }`
- CLI entrypoint: [`server/cli.php`](../../../server/cli.php) — see [cli.php behavior](../entrypoints/cli.md)

## Important note: `loadBackend()` returns a cached instance

`loadBackend()` returns a **cached backend instance** (singleton-like per backend name), not a new object on every call.
If `loadBackend($name, $login)` is called with `$login` and the backend is already loaded, it switches credentials on the **same instance** via `setLogin()`.

