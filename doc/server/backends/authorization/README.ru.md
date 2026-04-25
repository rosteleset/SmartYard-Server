# Backend: Authorization (`server/backends/authorization/`)

Backend authorization определяет, разрешён ли запрос, и предоставляет интроспекцию методов/прав.

## Содержание

- [`authorization.php`](./authorization.ru.md) — базовый класс (`backends\authorization\authorization`)
- Варианты:
  - [`allow`](./allow.ru.md) — allow-all (read-only capabilities)
  - [`internal`](./internal.ru.md) — права на базе БД (read-write capabilities)

