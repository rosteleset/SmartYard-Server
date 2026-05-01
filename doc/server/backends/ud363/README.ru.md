# Backend `ud363`

## Назначение

**Заготовка** под сценарий «HTTP file upload / slot» по смыслу **[XEP-0363: HTTP File Upload](https://xmpp.org/extensions/xep-0363.html)**. Имя модуля (**363**) — отсылка к **номеру XEP**, не к конкретному устройству.

Базовый класс и variant `internal` сейчас **пустые**: контракт появится при реализации выдачи слотов, хранения и политик retention (обычно в связке с backend `files`).

## Связь с XEP-0363

В XMPP протокол использует IQ и пространство имён `urn:xmpp:http:upload:0`: клиент запрашивает **slot** (пара **PUT** + **GET** URL), затем выполняет загрузку по HTTPS. Здесь это должно быть отражено на уровне доменной логики backend’а; конспект и рекомендации по безопасности — в **[точке входа `ud363.php`](../../entrypoints/ud363.ru.md)**.

## Код

- **Базовый класс**: `server/backends/ud363/ud363.php`.
- **Варианты**: `internal` (`internal/internal.php`).

## Конфигурация

Секция `backends.ud363` в `server/config/config.json`.

## Кто использует

HTTP [`server/ud363.php`](../../entrypoints/ud363.ru.md), при необходимости — обработчики из [`server/api/ud363/`](../../api/ud363/README.ru.md); в коде TT встречаются ссылки на вложения с ключом `ud363`.

См. также [индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).
