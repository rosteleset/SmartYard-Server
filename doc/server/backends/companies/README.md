# `companies` backend

## Purpose

Organizations (contractors/management companies): lists, CRUD.

## Code

- **Base class**: `server/backends/companies/companies.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.companies`.

## Main API (contract)

`getCompanies`, `getCompany`, `addCompany`, `modifyCompany`, `deleteCompany`.

## Callers

`households` backend and companies API.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

