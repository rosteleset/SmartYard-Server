# `issueAdapter` backend

## Purpose

External ticketing/issue tracker adapter.

## Code

- **Base class**: `server/backends/issueAdapter/issueAdapter.php`.
- **Variants**: `teledom`, `lanta`.

## Configuration

Key in `server/config/config.json`: `backends.issueAdapter`.

## Main API (contract)

`createIssue`, `listConnectIssues`, `commentIssue`, `actionIssue`.

## Callers

`mobile/issues/listConnect*.php`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

