# Address broadcasts

The **Notification / Оповещение** action is available on the house, street,
settlement, city, area and region pages. On the root address page (the list of
regions), it targets **all addresses in all regions**.

The form displays the selected scope and the number of distinct subscribers,
then asks for confirmation before queuing the message. Both `inbox` and `money`
actions remain available. Closing the page after the queue request succeeds
does not interrupt delivery.

## API

Both endpoints require the same permission as sending an individual inbox
message: `addresses/house/PUT` (`#same`), enforced by the existing API gateway.

- `GET /api/inbox/broadcast?by=regionId&query=1` returns
  `{"audience":{"count":123}}`. This preview is not cached and writes nothing.
- `POST /api/inbox/broadcast` accepts
  `{"by":"regionId","query":1,"title":"Notice","body":"Details","action":"inbox"}`
  and returns `{"queued":{"count":123}}` after a successful queue insertion.

`by` is one of `houseId`, `streetId`, `settlementId`, `cityId`, `areaId`,
`regionId`, or `all`. Node scopes require an existing positive integer `query`.
The root scope must be explicit: `by=all`, with `query` omitted or `0`.
An invalid or missing node ID never falls back to a global broadcast.
`title` and `body` must be nonempty strings; `action` defaults to `inbox` and
can also be `money`.

## Audience and delivery semantics

- Recipients are existing mobile subscribers linked to flats in descendant
  houses. Unlinked accounts and orphan flat/subscriber links are excluded.
- Alternative address parent links are supported: a city directly in a region,
  a settlement directly in an area, and a house directly in a settlement.
- A subscriber linked to several matching flats or houses receives one queue
  entry. The root scope is not a broadcast to unlinked accounts.
- POST resolves the audience again; it may differ from the preview if links
  changed in the meantime. `queued.count` reports newly inserted queue rows,
  not confirmed deliveries.
- A single atomic `INSERT ... SELECT DISTINCT ... ON CONFLICT DO NOTHING`
  writes to the existing `houses_subscribers_messages` queue. Subscriber lists
  are not transferred to the browser or iterated in PHP.
- Identical messages already pending in the queue are not inserted again.
  This is **pending-message deduplication**, not a permanent idempotency key:
  resubmission can resend messages whose queue entries have already been consumed.
- The existing households minutely worker processes up to 1024 rows per run
  through `inbox::sendMessage`. Its delivery, push preferences and error/retry
  behavior are unchanged. Large broadcasts are gradual; a queued response does
  not guarantee successful push delivery.

## Installation and verification

No new database migration is needed; the existing bulk-message schema from
`v78_bulk_messages.sql` is reused. Update both server and client, reindex the
API through the normal update workflow (or `php server/cli.php --reindex`
from the checkout root), and ensure the households minutely worker is enabled.
The client cache version is bumped for the new module and translations.

Run from the checkout root with PHP + `pdo_pgsql`, a **test** PostgreSQL server,
and Node.js:

```sh
RBT_TEST_PG_DSN='pgsql:host=/path/to/test/socket;dbname=postgres' \
RBT_TEST_PG_USER=rbt_test php tests/address-broadcast.php
node --test tests/address-broadcast.test.cjs
```

`RBT_TEST_PG_PASSWORD` is optional. Database tests use only connection-local
temporary tables and roll back on exit; no notification backend is loaded.
They cover every address level and parent-link variant, deduplication, empty
audiences, invalid input, queue errors, refreshed membership and bulk insertion.
Frontend tests cover route scopes, permissions, preview, confirmation, escaping,
single-request sending and failures without contacting a server.

For manual acceptance on a test installation, check the action on each address
page and the root page; confirm the scope/count before submitting a test-only
message, then verify its inbox delivery after a minutely worker run. An account
without `addresses/house/PUT` must see no broadcast action and receive a denial
from both API methods.
