# Backend `households`

## Назначение

Центральный доменный backend: дома, подъезды, квартиры, абоненты, домофоны, интеграции SIP/ISDN, очереди задач и др.

## Код

- **Базовый класс**: `server/backends/households/households.php` (очень большой набор abstract-методов).
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.households`.

## Основные методы (контракт)

Десятки методов: `getFlats`, `addFlat`, домофоны, абоненты — см. файл базового класса.

## Кто использует

`mobile.php`, `asterisk.php`, `billing`, камеры, очередь, почти всё ядро доступа.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

