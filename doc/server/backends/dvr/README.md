# `dvr` backend

## Purpose

DVR integration: servers, per-camera tokens, archive and screenshot URLs.

## Code

- **Base class**: `server/backends/dvr/dvr.php`.
- **Variants**: `internal`, `custom`.

## Configuration

Key in `server/config/config.json`: `backends.dvr`.

## Main API (contract)

`getDVRServerForCam`, `getDVRTokenForCam`, `getDVRStreamURLForCam`, `getDVRServers`, `getUrlOfRecord`, `getUrlOfScreenshot`.

## Callers

`asterisk.php`, `plog`, archive UIs.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).

