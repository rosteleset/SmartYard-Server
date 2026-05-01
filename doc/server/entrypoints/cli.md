# `cli.php`

CLI entrypoint for installation, maintenance, cron slices, and **backend-specific** admin commands.

## Role in the stack

The script bootstraps the same core pieces as other server entrypoints (config, `loader.php`, `api.php`, base backend class), but sets `$cli = true` and never speaks HTTP. It connects to PostgreSQL (via `PDOExt`), Redis, and ensures **required** backends (`authentication`, `authorization`, `accounting`, `users`) load before running commands.

## Two ways to invoke commands

1. **Global (built-in) commands** — flags only, no backend prefix:

   ```text
   php server/cli.php --reindex
   php server/cli.php --cron=daily
   ```

   These are registered under the internal key `#` in the global CLI registry (see below).

2. **Backend-scoped commands** — first argument is the **backend name** from `config.json` → `backends`, then flags for that backend:

   ```text
   php server/cli.php mongo --list-indexes
   ```

   The dispatcher loads that backend instance and calls its `cli($args)` method. The backend is responsible for interpreting which flag was passed (often several flags are documented under one `cliUsage()` tree).

Detection: if `argv[1]` exists and does not start with `--` (e.g. it is `mongo`), it is treated as the backend name, removed from the parsed `$args` map, and `cli("run", "<backend>", $args)` runs. Otherwise dispatch uses `#`.

## Where commands are registered

### `server/cli/` and `server/cli/custom/`

After `chdir` to `server/`, `cli.php` scans:

- `server/cli/*.php`
- `server/cli/custom/*.php`

Each file is expected to define `namespace cli { class <Name> { ... } }` matching the basename (e.g. `cron.php` → `class cron`). The constructor receives **`&$global_cli`** and writes command entries.

Built-in modules use the **`#`** slot — “global” namespace — and group them under human-readable section titles, for example:

- `$global_cli["#"]["cron"]["cron"] = [ "value" => [...], "exec" => ... ];`
- `$global_cli["#"]["initialization and update"]["reindex"] = [ ... ];`

So: **`#` = commands implemented in `server/cli/*`, not tied to a named backend instance.**

### Backends (`server/backends/...`)

For each backend in `config["backends"]`, `cli.php` calls `loadBackend($name)` and **`cliUsage()`**. The returned array is **merged** into `$global_cli[$backendName]`.

- Base class `backend` implements `cliUsage()` as `[]` and `cli($args)` as “no-op”.
- Concrete backends override `cliUsage()` to return nested sections: *section title* → *`--flag-name`* → metadata (`description`, optional `value` / `params`, optional `stage`, etc.).
- When a user runs `php cli.php <backend> ...`, the matching path calls **`$instance->cli($args)`** so the backend can branch on `$args` (see e.g. `files/mongo` for GridFS maintenance flags).

**Important:** For `#` commands, the registry’s `"exec"` callable runs directly. For a named backend, **any** registered flag match triggers the same **`cli($args)`** on that backend; the method should handle all flags that backend advertises in `cliUsage()`.

## Lifecycle stages (`init` / `pre` / `run`)

`cli($stage, $backend, $args)` only runs handlers whose entry matches the current stage (default stage is `run` if omitted).

- **`init`** — runs **before** the database connection is opened (after config is read). Use for early hooks that must not depend on PDO.
- **`pre`** — runs after DB/Redis and required backends are up, **before** `startup()` (process row, maintenance gate). Entries can set `"stage" => "pre"` (e.g. exit maintenance mode without hitting the maintenance blocker).
- **`run`** — normal command execution after `startup()`.

## Argument parsing

- Common options: `--parent-pid=<pid>`, `--debug`.
- Other `argv` entries are parsed as `--name` or `--name=value` into an `$args` associative array (keys include the `--` prefix as in code).

## Help output

If no matching command runs, **`cliUsage()`** rebuilds the registry from all backends’ `cliUsage()`, then prints usage grouped by backend (`#` first as `usage: … <params>`, then `usage: … <backend> <params>`).

## Operational behavior (short)

- **`core_running_processes`**: long-running jobs can register; duplicate invocations with the same param string may exit early as “already running”.
- **Maintenance mode**: unless skipped, a DB flag can block CLI until `--exit-maintenance-mode` (typically `stage: pre`).
- **Cron**: scheduled slices call `cli.php` with `--cron=<part>`; the cron CLI module iterates **all** backends and invokes each backend’s `cron($part)` with Redis locking (see `server/cli/cron.php`).

## Related documentation

- [Backend base class and `cli` / `cliUsage` hooks](../backends/backend.md)
- [Dynamic loading: `loadBackend`](../utils/loader.md)
- Built-in CLI modules live under [`server/cli/`](../../../server/cli/) (and optional [`server/cli/custom/`](../../../server/cli/custom/)).
