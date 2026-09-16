# Multiple servicing organizations per house

House creation and editing accept multiple organizations in the **Servicing
organizations** selector. An empty selection removes all assignments.

## Storage and upgrade

DB migration 98, registered in `server/data/install.json`, creates
`addresses_houses_companies(address_house_id, company_id)`. The composite
primary key prevents duplicate links; foreign keys cascade when a house or
organization is deleted. A reverse index supports organization/device lookup.

Run the normal database upgrade (`php cli.php --init-db` from `server/`)
before serving the new code. The standard upgrade performs a backup and enters
maintenance mode. The migration copies every positive legacy `company_id`
and older `company` assignment, deduplicates them, then removes both columns.
Zero/NULL means no assignment. The migration is atomic and repeatable: forcing
it again does not recreate links that were subsequently removed.

A legacy reference to a nonexistent organization stops the migration with a
foreign-key error and leaves the old data intact. Reconcile those references
before retrying; they are never silently skipped. As this removes old storage,
rolling back the application also requires restoring the pre-upgrade database.
`current_schema.sql` is a historical snapshot, not the migration entry point.

## API and integrations

House GET, lists and search return `companyIds`, a sorted array of integer IDs
(or `[]`). House POST/PUT and billing address import accept `companyIds` as the
complete set. IDs must be positive, existing organizations; duplicates are
removed. Invalid updates roll back both the house and the links.

- PUT/import without company fields preserves the existing links.
- `companyIds: []` explicitly clears them.
- `companyIds` takes precedence over deprecated `companyId`.
- The legacy scalar `companyId` response is computed as the lowest linked ID,
  or `0`. There is no primary organization or scalar storage anymore.
- Legacy scalar writes still work for zero/one relationship. For a house with
  multiple relationships, sending the unchanged scalar projection preserves
  all links; another scalar is rejected (`useCompanyIds`) instead of silently
  removing organizations. Use an explicit array to replace or clear the set.

Changing the organizations schedules the house's devices for configuration so
their RFID sets reflect both additions and removals. Company-scoped device
lookup and RFID collection use all links, including devices shared by houses.

Custom integrations such as SuperKey should use intersection with
`house.companyIds`, not the deprecated scalar projection. A scalar-only
integration can see only the lowest-ID organization until it is updated.

## Regression tests

PHP with `pdo_pgsql`, PostgreSQL with `fuzzystrmatch`, and Node.js are required.
Use a dedicated test database whose role can create schemas and the trusted
`fuzzystrmatch` extension; the PHP test creates a random schema
and removes only that schema on exit. No devices or external services are used.

```sh
RBT_TEST_DSN='pgsql:host=127.0.0.1;port=5432;dbname=rbt_test' \
  RBT_TEST_USER=rbt_test RBT_TEST_PASSWORD=... php tests/house_companies.php
node tests/house_companies_ui.js
```

The tests cover legacy migration/rollback/retry, CRUD, API compatibility,
billing import, invalid IDs, nested transactions, FK cleanup, queueing, RFID
isolation, and multi-selection/hidden-field behavior in the house forms.
