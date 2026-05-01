# `tt` API (`server/api/tt/`)

## Purpose

Ticketing (TT): projects, issues, workflow, attachments, filters.

## Routing (Web UI)

The SPA hits `server/frontend.php` (and peers). **`/api/<module>/<endpoint>`** maps to `server/api/<module>/<endpoint>.php`: class **`api\<module>\<endpoint>`**, HTTP verb selects the static method (`GET`, `POST`, `PUT`, `DELETE`). Optional override: **`server/api/<module>/custom/<endpoint>.php`** → **`api\<module>\custom\<endpoint>`**.

Response envelope: [`api.php` base class](./api.md).


## Endpoint files

| File | Path (under `/api/tt`) |
|------|-------------------------------------|
| `action.php` | `/action` |
| `arrays.php` | `/arrays` |
| `bulkAction.php` | `/bulkAction` |
| `catalog.php` | `/catalog` |
| `comment.php` | `/comment` |
| `crontab.php` | `/crontab` |
| `customField.php` | `/customField` |
| `customFilter.php` | `/customFilter` |
| `favoriteFilter.php` | `/favoriteFilter` |
| `file.php` | `/file` |
| `filter.php` | `/filter` |
| `issue.php` | `/issue` |
| `issueTemplate.php` | `/issueTemplate` |
| `issues.php` | `/issues` |
| `journal.php` | `/journal` |
| `json.php` | `/json` |
| `lib.php` | `/lib` |
| `link.php` | `/link` |
| `printIssue.php` | `/printIssue` |
| `prints.php` | `/prints` |
| `project.php` | `/project` |
| `resolution.php` | `/resolution` |
| `role.php` | `/role` |
| `status.php` | `/status` |
| `suggestions.php` | `/suggestions` |
| `tag.php` | `/tag` |
| `tt.php` | `/tt` |
| `viewer.php` | `/viewer` |
| `workflow.php` | `/workflow` |

*`lib.php` and `json.php` are not standalone routes — included by other endpoints.*

See also the [API index](../README.md) and [`api.php`](../api.md).