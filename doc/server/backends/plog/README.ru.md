# Backend `plog`

## Назначение

Журнал событий доступа (звонки, открытия, лица, транспорт): дни с событиями, детализация по дню, константы типов событий.

## Код

- **Базовый класс**: `server/backends/plog/plog.php`.
- **Варианты**: `clickhouse`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.plog`.

## Основные методы (контракт)

`getEventsDays`, `getDetailEventsByDay`, методы по UUID события и др. — см. файл.

## Кто использует

API журнала, мобильный клиент, интеграция с `households`/`frs`/`dvr`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

