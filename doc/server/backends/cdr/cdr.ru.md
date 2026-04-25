# `server/backends/cdr/cdr.php`

## Назначение

Определяет базовый backend-класс для CDR (детализация звонков / call detail records).

Это “модульный” базовый класс, от которого должны наследоваться конкретные реализации (variant).

## Namespace и класс

- **Namespace**: `backends\cdr`
- **Класс**: `backends\cdr\cdr` (abstract)
- **Наследуется от**: `backends\backend`

## Контракт

Конкретные реализации backend’а CDR должны реализовать:

- `getCDR($phones, $dateFrom, $dateTo)`

### Параметры

- `$phones`: список телефонов (как приходит из API).
- `$dateFrom`: опциональная нижняя граница по времени.
- `$dateTo`: опциональная верхняя граница по времени.

### Возвращаемое значение

Возвращаемое значение используется в `server/api/cdr/cdr.php`:

- вернуть **данные** (структуру/массив) при успехе
- вернуть `false` при ошибке (API будет считать это ошибкой)

## Связанный код

- API endpoint: `server/api/cdr/cdr.php`
- Loader: `server/utils/loader.php` (`loadBackend("cdr")`)
- Конфигурация: `server/config/config.json` → `backends.cdr`

## Примечания

- В этом репозитории сейчас есть только базовый abstract-класс для `cdr`. Конкретная реализация должна лежать в:
  - `server/backends/cdr/<variant>/<variant>.php`
  и выбирается через `config["backends"]["cdr"]["backend"] = "<variant>"`.

