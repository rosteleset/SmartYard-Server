# PDOExt (`server/utils/PDOExt.php`)

`PDOExt` extends `PDO` and adds:

- storing the original DSN and parsing it into `protocol` + parameters;
- convenience helpers for common operations: `insert()`, `modify()`, `modifyEx()`, `get()`, `queryEx()`;
- a consistent error-handling style (via `setLastError()` + `error_log()`), with an option to suppress logging (`silent`);
- for SQLite: registration of user functions `mb_strtoupper` and `mb_levenshtein`.

## Constructor

**Signature**: `__construct($_dsn, $username = null, $password = null, $options = null)`

Behavior:

- stores the DSN in a private `$dsn` field;
- calls `parent::__construct(...)`;
- sets `PDO::ATTR_ERRMODE` to `PDO::ERRMODE_EXCEPTION`;
- detects the protocol via `parseDsn()["protocol"]` and, for `sqlite`, registers functions:
  - `mb_strtoupper` (1 argument),
  - `mb_levenshtein` (2 arguments).

Important:

- for `sqlite`, functions are always registered, so the runtime is expected to provide callable `mb_strtoupper` and `mb_levenshtein`.

## parseDsn()

**Purpose**: parses a DSN string into a protocol and a list of parameters.

**Returns**:

```php
[
  "protocol" => string,
  "params" => array,
]
```

Rules:

- DSN must contain `:` (otherwise the process terminates via `die(...)`).
- The prefix (protocol) must match `/^[a-z\d]+$/i` (otherwise `die(...)`).
- The part after the protocol is split by `;`.
  - If the DSN contains `=`, elements are parsed as `key=value`.
  - Otherwise `params` becomes a list with a single element (the whole substring after the prefix).

Implementation note:

- the `=` check is performed via `strpos($dsnWithoutPrefix, '=')` on the **whole string**, not per element — so mixed formats are handled uniformly for all elements.

## trimParams($map)

Trims whitespace for all values:

- if `$map` is a list (`array_is_list($map)`), trims each element;
- if it's an associative array, trims each value;
- keeps `null` values intact.

Used by `insert()` / `modify()` / `modifyEx()` to pass predictable parameters to `execute()`.

## insert($query, $params = [], $options = [])

**Purpose**: execute an INSERT (or any query where `lastInsertId()` is needed).

Behavior:

- `prepare($query)` → `execute(trimParams($params))`
- on success, tries to return `$this->lastInsertId()`
  - if `lastInsertId()` is not available / throws, returns `-1`
- if `execute()` returns `false`, returns `false`
- on errors:
  - if `"silent"` is not set: calls `setLastError(...)`, logs the exception and the SQL
  - returns `false`

Return values:

- `string`/`int` (as returned by `lastInsertId()`), or `-1`, or `false`.

## modify($query, $params = [], $options = [])

**Purpose**: execute a modifying query (UPDATE/DELETE/INSERT without needing `lastInsertId()`).

Behavior:

- `prepare($query)` → `execute(trimParams($params))`
- on success returns `$sth->rowCount()`
- if `execute()` returns `false`, returns `false`
- error handling is the same as `insert()` (including `"silent"`).

Return values:

- `int` (affected rows) or `false`.

## modifyEx($query, $map, $params, $options = [])

**Purpose**: apply modifications for a set of fields (a partial update driven by a mapping).

Parameters:

- `$query`: a template string for `sprintf($query, $db, $db)`.
  - expected to contain **2 placeholders** for the same DB column name `$db` (e.g. for `SET col=:col` and/or for `WHERE`).
- `$map`: `dbColumn => paramName`
- `$params`: input parameters (keyed by `paramName`)

Logic:

- iterates over `$map`
- if `$params` contains `$paramName`, builds `sprintf($query, $db, $db)`
- executes with parameters `[$db => $params[$paramName]]`
- accumulates `$sth->rowCount()` into `$mod`
- returns `$mod` (or `false` on error)

`"silent"` works the same way as in `insert()` / `modify()`.

## queryEx($query)

**Purpose**: execute a query without parameters and return all rows.

Behavior:

- `prepare($query)` → `execute()`
- on success: `fetchAll(PDO::FETCH_ASSOC)`
- on failure: returns an empty array `[]`

Errors are not caught here — an exception can bubble up (because ERRMODE is EXCEPTION).

## get($query, $params = [], $map = [], $options = [])

**Purpose**: a generic SELECT helper with optional result mapping and “collapse” options.

Execution branches:

- if `$params` is not empty:
  - `prepare($query)` → `execute($params)` (note: `trimParams()` is **not** used here)
  - `fetchAll(PDO::FETCH_ASSOC)`
- otherwise:
  - calls `queryEx($query)`

Mapping (`$map`):

- if `$map` is provided, it is expected to be `sourceField => targetField`
- for each row it builds a new array: `[$targetField] = $row[$sourceField]`

Options (`$options`):

- `"singlify"`: if the final dataset contains exactly 1 row — return that row, otherwise `false`
- `"fieldlify"`: if the final dataset contains exactly 1 row — return the first field value of that row, otherwise `false`
- `"silent"`: suppress error/SQL logging (for `PDOException`)

Return values:

- `array` (list of rows, or a single row in `"singlify"` mode)
- scalar in `"fieldlify"` mode
- `false` if expectations are not met (`singlify`/`fieldlify`) or on errors.

## External dependencies / runtime expectations

- `setLastError(...)` must exist (used in exception handlers).
- For `sqlite`, callable `mb_strtoupper` and `mb_levenshtein` must be available (they are registered as SQLite functions).

