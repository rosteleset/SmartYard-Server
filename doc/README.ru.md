# Индекс документации RBT

Эта папка содержит документацию проекта `rbt`.

## О проекте

**RoBoT / RBT** — открытая платформа IP-домофонии и видеонаблюдения (автономная серверная часть и SPA-клиент). В апстриме проект известен как **SmartYard-Server**.

- Сайт: [sesameware.com](https://sesameware.com)
- Важные замечания (апстрим): [important.md](https://github.com/rosteleset/SmartYard-Server/blob/main/important.md)
- Вики: [GitHub Wiki](https://github.com/rosteleset/SmartYard-Server/wiki)
- Генерируемая документация API (WiP, апстрим): [SERVER API](https://rosteleset.github.io/SmartYard-Server/doc/api/), [MOBILE API](https://rosteleset.github.io/SmartYard-Server/doc/mobile/)
- [Changelog (апстрим)](https://github.com/rosteleset/SmartYard-Server/blob/main/changelog.md)

## Содержание

## Киты проекта (на чём стоит система)

- **Клиент**: **SPA** (single-page application), общается с сервером **только по HTTP API**. **Server-side rendering (SSR) отсутствует**.
- **Сервер**: набор в основном **ванильных PHP-скриптов** с **минимумом зависимостей** (Composer используется, но намеренно держится небольшим).

## Дорожная карта (целевое оглавление)

Ниже — структура документации, которую планируем заполнить. Некоторые страницы могут ещё не существовать — ссылки отражают план.

### Архитектура

- [Обзор](./architecture.ru.md)
- [Глоссарий домена](./domain/glossary.ru.md)
- [Обзор модели данных](./domain/data-model.ru.md)

### API

- [Web UI API (frontend)](./api/frontend.ru.md)
- [Mobile API](./api/mobile.ru.md)
- [Billing API](./billing.api.ru.md)
- [Соглашения API (роутинг, auth, ошибки, кеширование)](./api/conventions.ru.md)

### Клиент / Кастомизация

- [Обзор клиента (SPA, модули, роутинг)](./client/overview.ru.md)
- [Конфигурация клиента](./client/config.ru.md)
- [Модули клиента](./client/modules.ru.md)
- [Модули SPA (`client/modules`)](./client/spa-modules.ru.md)
- [Кастомизация: customFields](./customFields.ru.md)
- [Примеры кастомизации](./examples/client/README.ru.md)

### Сервер / Утилиты

- [Обзор сервера (ванильный PHP, точки входа)](./server/overview.ru.md)
- [Точки входа](./server/entrypoints/README.ru.md)
  - [frontend.php (шлюз Web UI API)](./server/entrypoints/frontend.ru.md)
  - [mobile.php (шлюз Mobile API)](./server/entrypoints/mobile.ru.md)
  - [cli.php (CLI утилиты)](./server/entrypoints/cli.ru.md)
  - [asterisk.php (интеграция с Asterisk)](./server/entrypoints/asterisk.ru.md)
  - [internal.php (шлюз внутреннего API)](./server/entrypoints/internal.ru.md)
  - [kamailio.php (интеграция с Kamailio)](./server/entrypoints/kamailio.ru.md)
  - [wh.php (вебхуки)](./server/entrypoints/wh.ru.md)
  - [ud363.php (HTTP upload / заготовка под XEP-0363)](./server/entrypoints/ud363.ru.md)
  - [qr.php (QR endpoint)](./server/entrypoints/qr.ru.md)
  - [test.php (локальные тесты)](./server/entrypoints/test.ru.md)
- [Реализация API (server/api)](./server/api/README.ru.md)
- [Базовый класс API (`server/api/api.php`)](./server/api/api.ru.md)
- [Бэкенды (server/backends)](./server/backends/README.ru.md)
- [Базовый класс backend’ов (`server/backends/backend.php`)](./server/backends/backend.ru.md)
- [Backend `groups`](./server/backends/groups/README.ru.md)
- [Утилиты (server/utils) — каталог всех .php](./server/utils/README.ru.md)
  - [PDOExt](./server/utils/PDOExt.ru.md)
  - [loader.php (загрузчики)](./server/utils/loader.ru.md)
- [Вспомогательные сервисы (`server/services`)](./server/services/README.ru.md)

### Хранилища и сервисы

- [PostgreSQL (PDO)](./server/storage/postgresql.ru.md)
- [Использование Redis](./server/storage/redis.ru.md)
- [Использование MongoDB (files, GridFS)](./server/storage/mongodb.ru.md)
- [Использование ClickHouse](./server/storage/clickhouse.ru.md)

### Телефония и realtime

- [Интеграция с Asterisk](./asterisk/README.ru.md)
- [Интеграция с Kamailio](./server/kamailio/README.ru.md)
- [Интеграция с MQTT](./server/mqtt/README.ru.md)

### TT (тикеты/воркфлоу)

- [Обзор TT](./tt/README.ru.md)
- [Workflows (Lua)](./tt/workflows.ru.md)
- [Фильтры и viewer’ы](./tt/filters-and-viewers.ru.md)
- [Примеры](./examples/tt/README.ru.md)

### Оборудование

- [IS: syslog events](./hardware/is/syslog_events.ru.md)
- [QTech: syslog events](./hardware/qtech/syslog_events.ru.md)

### Установка и эксплуатация

- [Оглавление установки](./install/README.ru.md) (файлы гайдов в [`install/`](../install/))
- [Crontab и плановые задачи](./server/operations/crontabs.ru.md)
- [Режим обслуживания (maintenance)](./server/operations/maintenance.ru.md)
- [Бэкапы и восстановление](./server/operations/backups.ru.md)

### Примеры

- [Примеры сервера](./examples/server/README.ru.md)
- [Примеры кастомного сервера](./examples/custom/server/README.ru.md)
- [Примеры кастомного клиента](./examples/custom/client/README.ru.md)


