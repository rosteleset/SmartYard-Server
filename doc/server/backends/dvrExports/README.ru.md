# Backend `dvrExports`

## Назначение

Фоновая выгрузка фрагментов архива в файловое хранилище и уведомления в inbox.

## Код

- **Базовый класс**: `server/backends/dvrExports/dvrExports.php`.
- **Варианты**: `mongo`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.dvrExports`.

## Основные методы (контракт)

`addDownloadRecord`, `checkDownloadRecord`, `runDownloadRecordTask`; CLI `--run-record-download`.

## Кто использует

CLI (`cli.php`), связка с `files` и `inbox`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

