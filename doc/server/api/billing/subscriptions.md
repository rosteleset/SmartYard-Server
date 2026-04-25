# `/api/billing/subscriptions` — sync contracts auto-blocking

Implemented in `server/api/billing/subscriptions.php`.

## Auth and permissions

- Requires `Authorization: Bearer <token>`.
- Access is controlled by `authorization->allow()` in `server/frontend.php`.

## Dependencies

- **Entry point / dispatch**: `server/frontend.php` → `server/api/billing/subscriptions.php` → class `\api\billing\subscriptions`.
- **Backends**:
  - `billing` backend: `syncAutoBlockByContracts(subscribers, defaultAction)`
- **Notes**:
  - The handler filters/whitelists fields from `subscribers[]` before passing to backend.

## POST `/api/billing/subscriptions`

### Body

- `subscribers` (object[]): subscriber items used for sync.
  - The handler forwards (if present): `isActive`, `subscriberID`, `agreement`, `addressText`, `login`, `password`, `phones`, `buildingUUID`, `flatNumber`.

### Responses

- **Success 200**: `{"subscriptions": <syncResult>}`
- **Error 400**: `{"error":"unknown"}` when backend returns false (handler uses `ANSWER(false)`).

### Implementation caveats

- If backend `billing` is missing, the handler returns a plain string `"error"` (not a structured API error). This is a code-path to be aware of for clients.

