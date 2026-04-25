# `server/api/cdr/cdr.php`

## Маршрут

- **Метод**: `POST`
- **Путь**: `/api/cdr/cdr`

## Назначение

Возвращает записи CDR (детализация звонков / call detail records) для списка телефонов и опционального диапазона дат.

Endpoint — тонкая обёртка: вся логика делегируется backend’у `cdr` через `loadBackend("cdr")->getCDR(...)`.

## Входные параметры

Параметры тела запроса (как используются в реализации):

- `phones` (`string[]`) — список телефонов.
- `dateFrom` (`timestamp`, опционально)
- `dateTo` (`timestamp`, опционально)

## Выходные данные

Endpoint возвращает `api::ANSWER(...)` на основе результата backend’а:

- при успехе: payload под ключом `"cdr"`
- при ошибке: ошибка (в текущем коде используется строка `"404"`, если backend вернул `false`)

## Зависимости

- Backend: `loadBackend("cdr")`
- Метод backend’а: `getCDR($phones, $dateFrom, $dateTo)`

## Примечания

- В репозитории сейчас есть только **базовый класс backend’а** `cdr` (`server/backends/cdr/cdr.php`). Чтобы endpoint работал, нужно добавить конкретную реализацию (variant) и включить её в конфиге сервера (`config["backends"]["cdr"]`).

