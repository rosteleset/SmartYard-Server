# Расширения CLI у backend’ов

Backend добавляет подкоманды для [`cli.php`](../entrypoints/cli.ru.md), переопределяя `cliUsage()` и `cli($args)` у [базового `backend`](./backend.ru.md). В дереве исходников так сделаны только перечисленные ниже backend’ы.

Вызов:

```text
php server/cli.php <ключВConfig> --флаг ...
```

`<ключВConfig>` — ключ в `config.json` → `"backends"` (например `files`, а не каталог `mongo`).

## Сводная таблица

| Ключ | PHP | Роль |
|------|-----|------|
| `files` | `backends/files/mongo/mongo.php` | Индексы GridFS, очистка по expire, массовая правка expire, перенос в `extfs` |
| `extfs` | `backends/extfs/internal/internal.php` | Удаление с диска «осиротевших» файлов без записи в GridFS |
| `mkb` | `backends/mkb/internal/internal.php` | Текстовый и полевые индексы в Mongo-коллекции одного логина MKB |
| `users` | `backends/users/internal/internal.php` | Админское отключение 2FA по логину |
| `households` | `backends/households/internal/internal.php` | Импорт RFID из CSV по дому |
| `tt` | `backends/tt/tt.php` + `tt/internal/internal.php` | Выгрузка артефактов TT на диск; заливка viewer’ов с диска; пересборка индексов задач |
| `dvrExports` | `backends/dvrExports/dvrExports.php` | Одна задача выгрузки DVR и уведомление через inbox |

Остальные backend’ы оставляют пустой `cliUsage()` и no-op `cli()`.

---

## `files` (GridFS, вариант `mongo`)

**Контекст:** имя БД Mongo из конфига backend’а; коллекции GridFS — `fs.files` / `fs.chunks`. Часть команд совпадает по смыслу с cron (`cleanup`, `compact`).

### `--list-indexes`

`listIndexes()` для `fs.files`, печатает **имя** каждого индекса и итоговое число. Выход 0.

### `--create-indexes`

1. Постранично (`searchFiles`, шаг 1024) обходит все файлы GridFS и собирает все встреченные ключи `metadata.*`.
2. Формирует список полей: `filename`, `uploadDate`, `md5` плюс уникальные `metadata.<ключ>`.
3. Для каждого поля создаёт восходящий индекс с именем `index_<поле>` на `fs.files` (ошибки по отдельным индексам глотаются).
4. Печатает, сколько индексов создано/пересоздано. Выход 0.

### `--drop-indexes`

Снимает с `fs.files` все индексы, чьё **имя** начинается с `index_`. Печатает число удалённых. Выход 0.

### `--create-index=<поле1[,поле2,...]>`

Составной восходящий индекс на `fs.files`, имя `manual_index_<...>`. Выход 0.

### `--drop-index=<имя>`

Снимает индекс с `fs.files` по **точному** имени. Выход 0.

### `--cleanup`

`cleanup()`: выбирает документы `fs.files`, у которых `metadata.expire` **меньше** текущего Unix-времени, для каждого вызывает `deleteFile()` (удаляет файл и чанки). Выход 0. Та же логика, что у cron `5min` для этого backend’а.

### `--move-to-extfs`

Нужен успешный `loadBackend("extfs")`. Опционально `--query=<json>` — дополнительный фильтр Mongo (невалидный JSON → выход 1).

**Выборка:** `length > 0`, нет `metadata.expire`, `metadata.external` отсутствует или `false`; при необходимости пересечение с вашим JSON.

**На каждый файл:** `metadata.external = true`, `realUploadDate` из `uploadDate`, поток читается из GridFS, `addFile()` (запись в текущую связку хранилища — обычно с участием `extfs`), затем `deleteFile()` по id GridFS. Прогресс — точки в stdout. Внутри цикла и после пачки вызывается `compact` для `fs.chunks`. Печатает число перенесённых файлов. Если `extfs` не загрузился — сообщение и выход 0.

### `--force-expire=<дата>`

`strtotime` для даты; если не распознано — переход к `parent::cli()`.

Иначе опционально `--query=<json>` (ошибка JSON → выход 1). Запрос: документы с **существующим** `metadata.expire`, где значение **ещё не** равно новому timestamp (проверка и для int, и для string). При необходимости AND с фильтром. `updateMany` выставляет `metadata.expire`. Печатает matched / modified. Выход 0.

---

## `extfs`

### `--cleanup`

`cleanup()`:

- поднимает backend `files`;
- рекурсивно обходит корень `extfs` из конфига;
- для каждого файла имя файла трактуется как md5-id для поиска `metadata.md5id` в `files`;
- если `searchFiles` ничего не нашёл — печатает строку и **удаляет** файл с диска.

Печатает, сколько файлов удалено. Выход 0.

**Смысл:** убрать файлы на диске, для которых в GridFS/метаданных уже нет ссылки.

---

## `mkb`

Имя БД из конфига; **имя коллекции Mongo = строка логина** из аргумента CLI.

### `--create-indexes=<login>`

`createIndexes`: текстовый индекс `fullText` по полям задач (язык из `config["language"]` или `en`) и восходящие `index_<поле>` для `type`, `author`, `name`, `subject`, `color`, `body`, `desk`, `date`, `inbox`, `done`. Печатает число созданных индексов. Выход 0.

### `--drop-indexes=<login>`

Список индексов коллекции `$login`, удаляет все, **кроме** `_id_`. Выход 0.

---

## `users`

### `--disable-2fa=<login>`

`getUidByLogin`; при отсутствии — `user not found`. Иначе `twoFa($uid, false)`. Печать успех/неуспех. Выход 0.

---

## `households`

### `--rf-import=<csv>` вместе с `--house-id=<id>`

1. `getFlats("houseId", id)` → карта **номер квартиры → flatId**; без квартир — выход с ошибкой.
2. Нет файла — выход с ошибкой.
3. Строки CSV, разделитель запятая. Без `--rf-first`: кол.0 = квартира, кол.1 = RFID. С `--rf-first`: кол.0 = ключ, кол.1 = квартира.
4. Для каждой пары при наличии квартиры — `addKey(ключ, 2, flatId, "imported …")`, счётчик успехов, построчный лог, итог. Выход 0.

`addKey` проверяет длину RFID (6–32), пишет в `houses_rfids`, дергает `queue`, если есть.

---

## `tt`

Порядок: сначала **`cli()` в `internal`** (индексы), затем `parent::cli()` — логика из **`tt`** (экспорт/замена). Флаги секции `files` срабатывают только если не совпала ветка индексов.

### Экспорт / замена (`tt.php`, секция `files`)

Каталоги: `server/data/files/...` (относительно расположения `tt.php`).

| Флаг | Поведение |
|------|-----------|
| `--export-workflows` | все workflow → `workflows/<id>.lua` |
| `--export-filters` | каждый фильтр → `filters/<id>.json` |
| `--export-viewers` | каждый viewer → `viewers/<filename>.js` |
| `--replace-viewer=<имя.js>` | viewer с таким `filename.js`; если файл есть на диске — чтение и `putViewer` |
| `--replace-all-viewers` | скан `viewers/`; для каждого известного `.js` — `putViewer` из файла; счётчик замен |

### Индексы (`tt/internal/internal.php`)

Коллекции Mongo именуются **акронимом проекта** (`--project`, где обязателен).

| Флаг | Поведение |
|------|-----------|
| `--list-indexes` | нужен `--project`; имена индексов коллекции |
| `--create-indexes` | `reCreateIndexes()`: для каждого проекта пересобирается **fullText** по настройкам (subject/description/comments/searchable custom fields), хэш в Redis `FTS:<acronym>`; затем синхронизируются однополевые `index_*` (стандартный набор полей + индексируемые custom fields) — лишние `index_*` снимаются |
| `--drop-indexes` | по всем проектам снимает индексы с именами на `index_*` |
| `--create-index=<поля>` | нужен `--project`; составной `manual_index_...` |
| `--drop-index=<имя>` | нужен `--project`; снятие по точному имени |

---

## `dvrExports`

### `--run-record-download=<record_id>`

Целочисленный id. Вызывается `runDownloadRecordTask` у конкретной реализации backend’а. Если вернулся **uuid**:

- `inbox`, `files`, `getFileMetadata`;
- сообщение абоненту с локализованным текстом (ссылка на мобильный API из конфига).

Выход 0.

---

## См. также

- [Поведение cli.php](../entrypoints/cli.ru.md)
- [Базовый backend: `cli` / `cliUsage`](./backend.ru.md#cli-хуки)
