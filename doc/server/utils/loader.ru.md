# `server/utils/loader.php`

Этот файл содержит динамические “загрузчики” серверных модулей, включая **загрузчик backend’ов**.

## `loadBackend($backend, $login = false)`

`loadBackend()` загружает backend по конфигу и возвращает **кешированный экземпляр**.

### Ключевое поведение: возвращает существующий экземпляр

Функция работает как **service locator**:

- хранит кэш (singleton-подобный) в глобальном массиве `$backends`
- повторные вызовы возвращают **тот же объект backend’а** для одного и того же имени `$backend`
- если передан `$login` и backend уже загружен, функция **переключает учётные данные** на этом же экземпляре через `setLogin($login)`

То есть `loadBackend()` **не создаёт новый объект** при каждом вызове.

### Как выбирается класс backend’а

Выбор реализации определяется конфигом сервера:

- `config["backends"][$backend]["backend"]` выбирает **вариант** (имя реализации).

Ожидаемая структура файлов:

- модуль: `server/backends/<backend>/<backend>.php`
- вариант: `server/backends/<backend>/<variant>/<variant>.php`

Ожидаемое имя класса:

- `backends\<backend>\<variant>`

### Custom variant (кастомизация проекта)

Чтобы для конкретного backend’а реализовать/расширить логику без правок core-вариантов, можно использовать **custom variant**.

- **Конфиг**: `config["backends"][<backend>]["backend"] = "custom"`.
- **Файл**: `server/backends/<backend>/custom/custom.php`.
- **Класс**: `backends\<backend>\custom`

Custom-класс может наследоваться от модульного базового класса `backends\<backend>\<backend>` (из `server/backends/<backend>/<backend>.php`), если он существует, и реализовывать контракт конкретного backend’а.

### Обработка ошибок

- Если backend не описан в `config["backends"]`, функция возвращает `false`.
- Если файлов нет или при загрузке/создании падает исключение:
  - пишется лог
  - вызывается `setLastError(i18n("cantLoadBackend", $backend))`
  - возвращается `false`

## Другие загрузчики в этом файле

- `loadExtension($extension, $login = false)` — загружает extension из `server/extensions/**`.
- `loadDevice(...)` — загружает hardware-классы по JSON-модели из `server/hw/**` (приоритет у `custom` класса, если есть).
- `loadConfiguration()` — загружает конфиг из `server/config/config.json`.

