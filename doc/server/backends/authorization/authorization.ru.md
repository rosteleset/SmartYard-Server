# `server/backends/authorization/authorization.php`

## Назначение

Определяет базовый класс backend’а authorization: `backends\authorization\authorization`.

Этот backend отвечает за:

- **принятие решения** allow/deny
- **список доступных методов** для пользователя
- **управление правами** (если variant это поддерживает)
- выдачу списка проиндексированных API-методов через `methods()`

## Namespace и класс

- **Namespace**: `backends\authorization`
- **Класс**: `backends\authorization\authorization` (abstract)
- **Наследуется от**: `backends\backend`

## `methods($_all = true)`

Возвращает карту проиндексированных API-методов из таблиц БД, которые заполняются через `reindex()`.

Форма результата:

- `methods[api][method][request_method] = aid`

Кеширование:

- кешируется через `cacheGet/cacheSet` под ключом `METHODS:{0|1}`.

Режим `$_all=false` фильтрует:

- методы из `core_api_methods_common`
- методы из `core_api_methods_by_backend`
- методы с непустым `permissions_same`

## Абстрактный контракт

Variant обязан реализовать:

- `getRights()`
- `setRights($user, $id, $api, $method, $allow, $deny)`
- `allowedMethods($uid)`

## `mAllow($api, $method = false, $request_method = false)`

Хелпер для проверки `allowedMethods($this->uid)`:

- если задан `$request_method`: проверяет точную тройку (api, method, request_method)
- если задан только `$method`: проверяет (api, method)
- если задан только `$api`: проверяет наличие api

## Связанный код

- API endpoints: `server/api/authorization/*`
- Reindex: `server/utils/reindex.php` заполняет `core_api_methods*`

