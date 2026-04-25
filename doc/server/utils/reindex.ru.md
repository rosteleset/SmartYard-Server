# `server/utils/reindex.php`

`reindex()` сканирует `server/api/**` и заполняет таблицы БД, которые используются подсистемой авторизации.

## Назначение

Построить/обновить индекс API-методов: список методов, HTTP-вербы и специальные флаги обработки прав.

Результат `reindex()` в первую очередь используется:

- `backends\authorization\authorization::methods()`
- `backends\authorization\internal` (для allow/deny и allowedMethods)

## Что индексируется

Для каждой папки `server/api/<api>/` сканируются:

- `server/api/<api>/*.php`
- `server/api/<api>/custom/*.php` (custom-версии имеют приоритет)

Далее файл подключается, и вызывается статический `index()` у класса API-метода, чтобы получить список поддерживаемых HTTP-методов.

## Как интерпретируется `index()`

`index()` может вернуть список или map.

- Если это список `["GET", "POST"]`, то элементы — HTTP-методы.
- Если это map `["GET" => "#common"]`, то ключ — HTTP-метод, а значение — специальный тег.

Спец-теги, которые понимает `reindex()`:

- `#common` → добавить AID в `core_api_methods_common`
- `#personal` → добавить AID в `core_api_methods_personal`
- `#same(api, method, request_method)` → записать алиас в `core_api_methods.permissions_same`
- любая другая строка → считается именем backend’а и пишется в `core_api_methods_by_backend`

## Генерация AID

Для каждой тройки (api, method, request_method):

- `aid = md5("$api/$method/$request_method")`

## Таблицы БД

`reindex()` очищает и пересоздаёт:

- `core_api_methods`
- `core_api_methods_common`
- `core_api_methods_by_backend`
- `core_api_methods_personal`

В конце удаляются некорректные ссылки `permissions_same`.

