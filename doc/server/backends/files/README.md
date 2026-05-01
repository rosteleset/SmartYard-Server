# `files` backend

## Purpose

File object storage with metadata and search (MongoDB/GridFS in the `mongo` variant).

## Code

- **Base class**: `server/backends/files/files.php`.
- **Variants**: `mongo`.

## Configuration

Key in `server/config/config.json`: `backends.files`.

## Main API (contract)

`addFile`, `getFile`, `getFileStream`, metadata, `searchFiles`, `deleteFile`, stream helpers.

## Callers

Widely used: TT, plog, households, `cs`, DVR export.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

