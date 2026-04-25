# `/api/billing/subscriptions` — синхронизация autoBlock по договорам

Реализация: `server/api/billing/subscriptions.php`.

## Авторизация и права

- Требуется `Authorization: Bearer <token>`.
- Доступ проверяется через `authorization->allow()` в `server/frontend.php`.

## Зависимости

- **Точка входа / dispatch**: `server/frontend.php` → `server/api/billing/subscriptions.php` → класс `\api\billing\subscriptions`.
- **Backend’и**:
  - backend `billing`: `syncAutoBlockByContracts(subscribers, defaultAction)`
- **Примечание**:
  - handler фильтрует/whitelist’ит поля из `subscribers[]` перед передачей в backend.

## POST `/api/billing/subscriptions`

### Body

- `subscribers` (object[]): элементы для синхронизации.
  - handler пробрасывает (если есть): `isActive`, `subscriberID`, `agreement`, `addressText`, `login`, `password`, `phones`, `buildingUUID`, `flatNumber`.

### Ответы

- **Успех 200**: `{"subscriptions": <syncResult>}`
- **Ошибка 400**: `{"error":"unknown"}`, если backend вернул `false` (используется `ANSWER(false)`).

### Важная оговорка реализации

- Если backend `billing` недоступен, handler возвращает plain string `"error"` (не структурированная ошибка API).

