# API `tt` (`server/api/tt/`)

## Назначение

Тикет-система (TT): проекты, задачи, workflow, вложения, фильтры.

## Роутинг (Web UI)

Запросы SPA обрабатывает `server/frontend.php` (и аналогичные entrypoints). Путь **`/api/<module>/<endpoint>`** соответствует файлу `server/api/<module>/<endpoint>.php`: класс **`api\<module>\<endpoint>`**, вызывается статический метод с именем HTTP-метода (`GET`, `POST`, `PUT`, `DELETE`). Если есть **`server/api/<module>/custom/<endpoint>.php`**, он замещает стандартную реализацию (класс **`api\<module>\custom\<endpoint>`**).

Подробнее про ответы API — [базовый класс `api.php`](./api.ru.md).


## Каталог endpoint-файлов

| Файл | Путь (относительно `/api/tt`) |
|------|----------------------------------------|
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

*Файлы `lib.php` и `json.php` не являются отдельными маршрутами — подключаются из других endpoint’ов.*

См. также [индекс API](../README.ru.md) и [базовый класс `api.php`](../api.ru.md).