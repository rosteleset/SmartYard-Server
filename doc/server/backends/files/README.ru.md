# Backend `files`

## Назначение

Объектное хранилище файлов (метаданные, поиск, потоки): MongoDB/GridFS в variant `mongo`.

## Код

- **Базовый класс**: `server/backends/files/files.php`.
- **Варианты**: `mongo`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.files`.

## Основные методы (контракт)

`addFile`, `getFile`, `getFileStream`, метаданные, `searchFiles`, `deleteFile`, утилиты потоков.

## Кто использует

Почти все подсистемы: TT, plog, households, `cs`, экспорт DVR.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

