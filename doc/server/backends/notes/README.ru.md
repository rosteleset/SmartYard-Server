# Backend `notes`

## Назначение

Пользовательские заметки в UI: список, добавление, удаление, порядок сортировки.

## Код

- **Базовый класс**: `server/backends/notes/notes.php`.
- **Варианты**: `internal`.

## Конфигурация

Ключ в `server/config/config.json`: `backends.notes`.

## Основные методы (контракт)

`getNotes`, `addNote`, `deleteNote`, `reorder`.

## Кто использует

`server/api/notes/*`.

См. также [общий индекс backend’ов](../README.ru.md) и [загрузчик `loader.php`](../../utils/loader.ru.md).

