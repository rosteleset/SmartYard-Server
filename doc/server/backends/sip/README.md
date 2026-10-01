# `sip` backend

## Purpose

SIP helpers: locate server settings, STUN metadata.

## Code

- **Base class**: `server/backends/sip/sip.php`.
- **Variants**: `internal`.

## Configuration

Key in `server/config/config.json`: `backends.sip`.

### Mobile call transport

`asterisk.php` uses these fields from the selected `backends.sip.servers[]` entry
when building an incoming-call push:

- `sip_mobile_transport`: `tls` opts in to SIP over TLS. Omitted, null, or other
  values retain the existing `tcp` transport.
- `sip_tcp_port`: TCP port; the existing fallback is `5060`.
- `sip_tls_port`: TLS port; omitted or null defaults to `5061`. A custom port is
  supported. This setting alone does not enable TLS.

For example, add the following fields to an existing Asterisk server entry:

```json
{
  "sip_mobile_transport": "tls",
  "sip_tls_port": 8443
}
```

Before enabling TLS, configure the matching listener in the deployed
`asterisk/pjsip.conf`, for example:

```ini
[transport-tls]
type = transport
protocol = tls
bind = 0.0.0.0:8443
cert_file = /etc/asterisk-tls/cert.pem
priv_key_file = /etc/asterisk-tls/key.pem
method = tlsv1_2
verify_client = no
require_client_cert = no
symmetric_transport = yes
```

Use a trusted certificate chain matching the server entry's `ip` hostname, grant
the Asterisk service read access to the certificate and private key without
making the key public, and open the chosen TCP port in the firewall. Reload or
restart Asterisk as required by its transport configuration. Certificate renewal
must also update the files used by Asterisk. Keep existing TCP/UDP listeners for
devices that still use them.

The mobile client must support `transport=tls` and the supplied port. This option
only changes SIP signaling advertised in the push; it does not enable SRTP,
change RTP or TURN settings, or change panel connections. It does not guarantee
that a carrier will allow the call.

Regression test (PHP CLI only, no database or push delivery):

```sh
php tests/mobile_sip_transport.php
```

## Main API (contract)

`server`, `stun`.

## Callers

`asterisk.php`.

See also the [backends index](../README.md) and [`loader.php`](../../utils/loader.md).
