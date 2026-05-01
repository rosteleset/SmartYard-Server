# `billing` backend

## Purpose

External billing integration: subscriber info, add-on services, contract↔flat bindings, address import, etc. Large base class with shared logic and provider abstracts.

## Code

- **Base class**: `server/backends/billing/billing.php`.
- **Variants**: `internal` and others as deployed.

## Configuration

Key in `server/config/config.json`: `backends.billing`.

## Main API (contract)

Many methods; key abstracts include `getSubscriberAccountInfo`, `getSubscriberAdditionalServices`; see `billing.php`.

## Callers

`server/api/billing/*`, `households` backend.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

