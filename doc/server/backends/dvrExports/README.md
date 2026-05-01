# `dvrExports` backend

## Purpose

Background DVR clip export to file storage plus inbox notifications.

## Code

- **Base class**: `server/backends/dvrExports/dvrExports.php`.
- **Variants**: `mongo`.

## Configuration

Key in `server/config/config.json`: `backends.dvrExports`.

## Main API (contract)

`addDownloadRecord`, `checkDownloadRecord`, `runDownloadRecordTask`; CLI `--run-record-download`.

## Callers

CLI entrypoint; integrates with `files` and `inbox`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

