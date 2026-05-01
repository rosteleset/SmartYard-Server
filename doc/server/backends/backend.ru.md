# `server/backends/backend.php` — базовый класс backend’ов

Этот файл определяет базовый класс для всех backend’ов: `backends\backend`.

Backend’ы — это подключаемые реализации доменных сервисов, которые настраиваются в `server/config/config.json` в секции `"backends"`.

## Namespace и класс

- **Namespace**: `backends`
- **Базовый класс**: `backends\backend` (abstract)

## Конструктор и базовые поля

### `__construct($config, $db, $redis, $login = false)`

Базовый конструктор подключает общие зависимости и определяет идентичность backend’а:

- **`$config`**: весь серверный конфиг (распарсенный JSON/JSON5).
- **`$db`**: дефолтное подключение/обёртка PDO.
- **`$redis`**: подключение к Redis.
- **`$login`**: опционально можно передать явно; иначе берётся из глобального `$params["_login"]` или `"-"`.

Также вычисляет:

- **`$uid`**:
  - `-1` для анонимного (`"-"`)
  - `0` для `"admin"`
  - иначе через `loadBackend("users")->getUidByLogin($login)`
- **`$backend`** и **`$variant`** из имени конкретного класса (через `get_class($this)` и разбиение по `\`).
- **`$bconfig`** как короткий доступ к `config["backends"][$backend]`.

### Общие поля

Класс хранит:

- `$config`, `$bconfig`
- `$db`, `$redis`
- `$login`, `$uid`
- `$backend`, `$variant`
- `$cache` (in-memory кеш конкретного экземпляра; используется в `cacheGet/cacheSet/unCache/clearCache`)

## Опциональные хуки/возможности

Backend может переопределять поведение. По умолчанию методы возвращают “безопасные” значения:

- `capabilities()` → `false`
- `cleanup()` → `false` (сборщик мусора)
- `allow($params)` → `false` (регулятор прав доступа)
- `usage($object, $id)` → `false` (проверка “используется ли объект”)
- `cron($part)` → `true` (плановые задачи `minutely/5min/hourly/daily/weekly/monthly`)
- `check()` → `true` (self-check/health-check)

## Хелперы учётных данных

- `setCreds($uid, $login)` — установить текущие креды.
- `setLogin($login)` — сменить логин и при необходимости пересчитать uid через backend `users`.

## Кеш backend’ов (Redis + in-memory)

Кеширование выполняется **на backend + на пользователя**.

Формат ключа:

- `CACHE:{BACKEND}:{key}:{uid}`

### `cacheGet($key)`

- Сначала пробует in-memory кеш.
- Если нет и `uid > 0`, читает из Redis и кладёт в in-memory.
- На промахе возвращает `false`.

### `cacheSet($key, $value, $memOnly = false)`

- Сохраняет JSON-значение в in-memory.
- Если `uid > 0` и не `$memOnly`, также пишет в Redis с TTL:
  - `config["redis"]["backends_cache_ttl"]`, иначе по умолчанию 3 дня.

### `unCache($key)`

- Удаляет значение из in-memory и из Redis (только если `uid > 0`).

### `clearCache()`

- Очищает in-memory и удаляет в Redis все ключи `CACHE:{BACKEND}:*`.
- Возвращает число удалённых ключей Redis.

## CLI хуки

Backend может расширять CLI:

- `cli($args)` → по умолчанию `false`
- `cliUsage()` → по умолчанию `[]` (используется для построения общей справки CLI)

Регистрация команд, вызов `php cli.php <backend> …` и стадии `init`/`pre`/`run`: [cli.php](../entrypoints/cli.ru.md).

## Связанный код

- Загрузка backend’ов: `server/utils/loader.php`, функция `loadBackend(...)`
- Конфигурация: `server/config/config.json` → `"backends": { ... }`
- Точка входа CLI: [`server/cli.php`](../../../server/cli.php) — см. [поведение cli.php](../entrypoints/cli.ru.md)

## Важно: `loadBackend()` возвращает кешированный экземпляр

`loadBackend()` возвращает **кешированный экземпляр backend’а** (singleton-подобно: один объект на имя backend’а), а не создаёт новый объект при каждом вызове.
Если `loadBackend($name, $login)` вызывается с `$login`, а backend уже загружен, то креды переключаются на **том же экземпляре** через `setLogin()`.

