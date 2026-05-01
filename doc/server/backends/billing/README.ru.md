# Backend `billing`

## Назначение

Интеграция с внешним биллингом: данные абонента, дополнительные услуги, привязка договоров к квартирам/домам, импорт адресной иерархии и т.д. Большой базовый класс с общей логикой и abstract-методами провайдера.

## Код

- **Базовый класс**: `server/backends/billing/billing.php`.
- **Варианты**: `internal` и др. по проекту.

## Конфигурация

Ключ в `server/config/config.json`: `backends.billing`.

## Основные методы (контракт)

Много методов; ключевые abstract — `getSubscriberAccountInfo`, `getSubscriberAdditionalServices`; см. также `setContractsBindings` и прочие в `billing.php`.

## Кто использует

`server/api/billing/*`, backend `households`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

