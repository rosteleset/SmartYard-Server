# Backend CLI extensions

Backends can register subcommands for [`cli.php`](../entrypoints/cli.md) by overriding `cliUsage()` and `cli($args)` on the [base `backend`](./backend.md) class. Only the backends below do so in this tree (as of the current code).

Invocation:

```text
php server/cli.php <backendKeyFromConfig> --flag ...
```

`<backendKeyFromConfig>` is the key under `config.json` → `"backends"` (e.g. `files`, not the PHP folder `mongo`).

## Summary table

| Backend key | PHP | Role |
|-------------|-----|------|
| `files` | `backends/files/mongo/mongo.php` | GridFS indexes, expiry cleanup, bulk expire edits, migration to `extfs` |
| `extfs` | `backends/extfs/internal/internal.php` | Delete orphan files on disk that GridFS no longer references |
| `mkb` | `backends/mkb/internal/internal.php` | Text + field indexes on one MKB user Mongo collection |
| `users` | `backends/users/internal/internal.php` | Admin disable 2FA by login |
| `households` | `backends/households/internal/internal.php` | Import RFID keys from CSV into a house |
| `tt` | `backends/tt/tt.php` + `tt/internal/internal.php` | Export TT assets to disk; sync viewers from disk; rebuild TT issue indexes |
| `dvrExports` | `backends/dvrExports/dvrExports.php` | Run one DVR export job and notify via inbox |

All other backends keep the default empty `cliUsage()` / no-op `cli()`.

---

## `files` (GridFS, `mongo` variant)

**Context:** MongoDB database name comes from backend config; collection is standard GridFS `fs.files` / `fs.chunks`. Several commands align with cron-driven `cleanup` / `compact` behavior.

### `--list-indexes`

Calls `listIndexes()` on `fs.files`, prints each index **name** (one per line), then a total count. Exits 0.

### `--create-indexes`

1. Scans **all** GridFS file documents in pages of 1024 (`searchFiles`), collecting every `metadata.*` field name seen across files.
2. Builds a list: `filename`, `uploadDate`, `md5`, plus each `metadata.<key>` (unique).
3. For each field, creates an ascending index named `index_<field>` on `fs.files` (errors swallowed per index).
4. Prints how many indexes were (re)created. Exits 0.

### `--drop-indexes`

Lists indexes on `fs.files`, drops every index whose **name** starts with `index_` (default GridFS/system indexes with other names are left). Prints count dropped. Exits 0.

### `--create-index=<field1[,field2,...]>`

Parses comma-separated field names, builds one compound ascending index on `fs.files` named `manual_index_<_field1_field2...>`. Prints 0 or 1 created. Exits 0.

### `--drop-index=<name>`

Lists `fs.files` indexes and drops the index whose **name** exactly equals the argument. Prints count dropped (0 or 1 in normal cases). Exits 0.

### `--cleanup`

Runs `cleanup()`: finds `fs.files` documents with `metadata.expire` **less than** current Unix time, deletes each via `deleteFile()` (removes GridFS file + chunks). Exits 0 (output comes from delete path). Same logic as cron `5min` for this backend.

### `--move-to-extfs`

Requires `loadBackend("extfs")` to succeed. Optional `--query=<json>` merges an extra Mongo filter (must be valid JSON; otherwise exits 1).

**Selection:** files with `length > 0`, **no** `metadata.expire`, and `metadata.external` missing or `false`. Optionally AND your JSON filter.

**Per file:** sets `metadata.external = true`, copies `uploadDate` to `metadata.realUploadDate`, reads the GridFS stream, calls `addFile()` (writes into configured storage — typically `extfs` when that backend is active), then `deleteFile()` on the GridFS id. Prints `.` per file. Runs Mongo `compact` on `fs.chunks` in an inner loop and again after the batch. Prints total files moved. Exits 0 (or prints “extfs is not available” if backend missing).

### `--force-expire=<date>`

Parses `<date>` with `strtotime()`. If invalid, falls through to `parent::cli()`.

Otherwise optional `--query=<json>` (invalid JSON → exit 1). Builds a query: documents that **have** `metadata.expire` and where expire is **not** already the new timestamp (checks both integer and string inequality). AND-merge optional filter. `updateMany` sets `metadata.expire` to the new Unix time. Prints matched vs modified counts. Exits 0.

---

## `extfs`

### `--cleanup`

Runs `cleanup()`:

- Loads the `files` backend.
- Recursively walks the configured `extfs` root directory.
- For each regular file, treats the **filename** as the md5 id used in `metadata.md5id` searches.
- If `files->searchFiles([ "metadata.md5id" => $uuid ])` returns **no** rows, prints `unused file found …`, **deletes** the file from disk, increments counter.

Prints total files deleted. Exits 0.

**Meaning:** after GridFS/metadata and extfs paths diverge (e.g. DB row removed but file left on disk), this reclaims disk space.

---

## `mkb`

Mongo database name from config; **collection name equals the MKB login** string passed on the CLI.

### `--create-indexes=<login>`

Calls private `createIndexes($login)`:

- Creates a **text** index named `fullText` over subject/body/tags/comments/subtasks fields (language from `config["language"]` or `en`).
- Creates ascending indexes `index_<field>` for: `type`, `author`, `name`, `subject`, `color`, `body`, `desk`, `date`, `inbox`, `done`.

Prints count of indexes created (or 0 if the helper returned `true` only). Exits 0.

### `--drop-indexes=<login>`

Lists indexes on collection `$login`, drops every index **except** `_id_`. Prints how many dropped. Exits 0.

---

## `users`

### `--disable-2fa=<login>`

Resolves login → uid via `getUidByLogin`. If missing, dies with `user not found`. Calls `twoFa($uid, false)` to clear 2FA state. Prints success or failure. Exits 0.

---

## `households`

### `--rf-import=<csv>` (with `--house-id=<id>`)

1. Loads flats for the house via `getFlats("houseId", houseId)`; builds a map **flat number → flatId**. Dies if no flats.
2. Dies if CSV path does not exist.
3. Reads CSV lines; splits by comma. **Without** `--rf-first`: column 0 = flat number, column 1 = RFID key. **With** `--rf-first`: column 0 = key, column 1 = flat.
4. For each pair, if flat exists, calls `addKey($key, 2, $flatId, "imported <timestamp>")` (`accessType` 2 = RFID-style access to flat). Counts successes; prints per-row messages and total keys imported. Exits 0.

`addKey` validates RFID string length (6–32) and inserts into `houses_rfids`, then notifies `queue` if present.

---

## `tt`

Dispatch order: **`internal`’s `cli()` runs first** (index commands), then `parent::cli($args)` (**`tt`** handles export/replace). Any `tt` “files” flag is handled only after index branches do not match.

### Export / replace (`tt.php` — `files` section)

Paths are under `server/data/files/` (relative to `tt.php` location: `../../data/files/...`).

| Flag | Behavior |
|------|----------|
| `--export-workflows` | `getWorkflows()` then for each workflow id `getWorkflow($id)` → write `workflows/<id>.lua`. |
| `--export-filters` | Each filter id → `getFilter($id)` → `filters/<id>.json`. |
| `--export-viewers` | Each viewer → write `viewers/<filename>.js` from stored code. |
| `--replace-viewer=<name.js>` | Finds viewer whose `filename` + `.js` equals the argument; if matching file exists on disk, reads it and `putViewer(field, name, code)`. |
| `--replace-all-viewers` | Scans `viewers/` directory; for each `.js` file that matches a known viewer filename, `putViewer` from disk content. Prints how many replaced. |

All exit 0 after work (or on first hard failure in replace paths).

### Indexes (`tt/internal/internal.php`)

Uses Mongo collections named by **project acronym** (`--project=<acronym>` where required).

| Flag | Behavior |
|------|----------|
| `--list-indexes` | Requires `--project`. Lists index names on that collection. |
| `--create-indexes` | `reCreateIndexes()`: for every TT project, rebuilds **fullText** index from project settings (subject/description/comments/custom searchable fields); stores a hash in Redis `FTS:<acronym>` to skip unchanged definitions. Then ensures standard single-field indexes (`index_*`) exist for assigned, author, catalog, created, etc., plus custom fields marked indexed — drops obsolete `index_*` not in the desired set. Prints total index operations counted. |
| `--drop-indexes` | For **each** project, drops indexes whose names start with `index_` (fullText handled separately in recreate). |
| `--create-index=<fields>` | Requires `--project`. Compound index `manual_index_<fields>` on that collection. |
| `--drop-index=<name>` | Requires `--project`. Drops index by exact name. |

---

## `dvrExports`

### `--run-record-download=<record_id>`

Casts id to int. Calls abstract `runDownloadRecordTask($recordId)` on the concrete `dvrExports` variant. If it returns a **uuid**:

- Loads `inbox` and `files`.
- Reads file metadata via `files->getFileMetadata($uuid)`.
- Sends a localized inbox message to `subscriberId` (mobile API link text includes config).

Exits 0 (also exits 0 when task does not return uuid — no inbox in that case).

---

## See also

- [cli.php behavior](../entrypoints/cli.md)
- [Base backend: `cli` / `cliUsage`](./backend.md#cli-hooks)
