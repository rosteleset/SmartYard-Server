# `/api/billing/addresses` — импорт адресной иерархии

Реализация: `server/api/billing/addresses.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/billing/addresses.php` → класс `\api\billing\addresses`.
- **Backend’и**:
  - backend `billing`: `importAddressHierarchy(items)`
  - внутри `importAddressHierarchy()` дополнительно подгружаются и требуются:
    - backend `addresses`
    - backend `households`
    - backend `customFields`
- **Хранилище / side effects**:
  - импорт делает upsert адресной иерархии/квартир в БД через указанные backend’и.

## POST `/api/billing/addresses`

### Body

- `addresses` (object[]): элементы иерархии (полный список полей см. в PHPDoc внутри `server/api/billing/addresses.php`).

### Ответы

- **Успех 200**: `{"addresses": <importResult>}`
- **Ошибка 400**: `{"error":"badRequest"}`, если `addresses` отсутствует или не массив.
- **Ошибка 400**: `{"error":"unknown"}`, если backend `billing` недоступен (handler делает `ANSWER(false)` без имени ошибки).

