# `ud363` backend

## Purpose

**Placeholder** for an “HTTP file upload / slot” flow aligned with **[XEP-0363: HTTP File Upload](https://xmpp.org/extensions/xep-0363.html)**. The name (**363**) refers to the **XEP number**, not a specific hardware SKU.

The abstract base class and the `internal` variant are currently **empty**; the real contract will appear once slot issuance, storage, and retention are implemented (often together with the `files` backend).

## Relation to XEP-0363

In XMPP, the spec uses IQ stanzas and `urn:xmpp:http:upload:0`: the client requests a **slot** (paired **PUT** + **GET** HTTPS URLs), then uploads. That behaviour should be reflected in this backend’s domain logic; narrative notes (security, headers, slot TTL) live under the **[`ud363.php` entrypoint](../../entrypoints/ud363.md)**.

## Code

- **Base class**: `server/backends/ud363/ud363.php`.
- **Variants**: `internal` (`internal/internal.php`).

## Configuration

`backends.ud363` in `server/config/config.json`.

## Callers

HTTP [`server/ud363.php`](../../entrypoints/ud363.md), and handlers under [`server/api/ud363/`](../../api/ud363/README.md) when wired; TT attachments may reference `ud363` metadata.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).
