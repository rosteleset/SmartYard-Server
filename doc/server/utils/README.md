# Utilities (`server/utils/`)

Shared procedural helpers for the server: included from entrypoints (`frontend.php`, `cli.php`, …) and from API/backends. Unlike `server/backends/`, this folder is a **function library**, not a plugin system.

## File catalog

| File | Role |
|------|------|
| [`apiExec.php`](../../../server/utils/apiExec.php) | cURL-based HTTP client for calling APIs (method, URL, JSON, Bearer); scripts and integrations. |
| [`apiResponse.php`](../../../server/utils/apiResponse.php) | `response()` variant with a large HTTP status map (mobile-style payloads); **distinct from** `response.php`. |
| [`clickhouse.php`](../../../server/utils/clickhouse.php) | `clickhouse` class: HTTP access to ClickHouse (sessions, queries). |
| [`clearCache.php`](../../../server/utils/clearCache.php) | `clearCache($uid)` — delete `CACHE:FRONT:*:uid` keys or all `CACHE:*` when `$uid === true`. |
| [`cleanup.php`](../../../server/utils/cleanup.php) | `cleanup()` — invoke each configured backend’s `cleanup()`. |
| [`debug.php`](../../../server/utils/debug.php) | `debugOn`, `debugMsg`, `logMsg`; optional `accounting->raw` when debugging. |
| [`email.php`](../../../server/utils/email.php) | `eMail($config, $to, $subj, $text)` — mail via PHPMailer (SMTP from config). |
| [`error.php`](../../../server/utils/error.php) | `getLastError()` / `setLastError()` — global last API error. |
| [`forgot.php`](../../../server/utils/forgot.php) | Password recovery (`forgot($params)`), used from `frontend.php` for `/accounts/forgot`; Redis tokens and email link. |
| [`functions.php`](../../../server/utils/functions.php) | Large shared helper set: `checkInt`, `checkStr`, `GUIDv4`, `array_diff_assoc_recursive`, etc. |
| [`i18n.php`](../../../server/utils/i18n.php) | `language()`, `i18n()` — locale from `Accept-Language` / config, translated strings. |
| [`installCrontabs.php`](../../../server/utils/installCrontabs.php) | `installCrontabs()` — installs the RBT cron block (markers `## RBT crons …`). |
| [`levenshtein.php`](../../../server/utils/levenshtein.php) | UTF-8 `mb_levenshtein` / ratio for fuzzy string work. |
| [`loader.php`](../../../server/utils/loader.php) | `loadBackend`, `loadConfiguration`, `loadExtension`, `loadDevice` — see [dedicated page](./loader.md). |
| [`PDOExt.php`](../../../server/utils/PDOExt.php) | PDO extension for this project — see [dedicated page](./PDOExt.md). |
| [`polyfills.php`](../../../server/utils/polyfills.php) | Polyfills (e.g. `apache_request_headers`) when missing in the runtime. |
| [`purifier.php`](../../../server/utils/purifier.php) | `htmlPurifier()` — sanitize HTML via HTMLPurifier. |
| [`reindex.php`](../../../server/utils/reindex.php) | API method DB indexing — see [dedicated page](./reindex.md). |
| [`response.php`](../../../server/utils/response.php) | `response($code, $data)` — JSON response, `X-Last-Error`, `accounting` logging; main Web UI path. |

## Detailed pages

- [`PDOExt.php`](./PDOExt.md)
- [`loader.php`](./loader.md)
- [`reindex.php`](./reindex.md)

See also the [server overview](../overview.md) and the [doc index](../../README.md).
